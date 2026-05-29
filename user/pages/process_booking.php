<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id'])) {
    header('Location: /teman_singgah/index.php?auth=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: payment_confirm.php');
    exit;
}

function validatePromo(mysqli $db, string $kode, int $user_id, int $jumlah_malam): array {
    if (empty($kode)) return ['valid' => false, 'message' => 'Kode kosong.'];

    $stmt = $db->prepare("
        SELECT * FROM promo_codes
        WHERE kode = ?
          AND status = 'aktif'
          AND berlaku_dari  <= CURDATE()
          AND berlaku_hingga >= CURDATE()
        LIMIT 1
    ");
    $stmt->bind_param('s', $kode);
    $stmt->execute();
    $promo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$promo) return ['valid' => false, 'message' => 'Kode promo tidak valid atau sudah kedaluwarsa.'];
    if ($jumlah_malam < $promo['min_malam']) return ['valid' => false, 'message' => "Promo ini minimal {$promo['min_malam']} malam."];
    if ($promo['maks_pakai'] !== null && $promo['sudah_dipakai'] >= $promo['maks_pakai']) return ['valid' => false, 'message' => 'Kuota promo sudah habis.'];

    if ($promo['maks_pakai_per_user'] !== null) {
        $stmt2 = $db->prepare("SELECT COUNT(*) AS total FROM promo_usage WHERE promo_id = ? AND user_id = ?");
        $stmt2->bind_param('ii', $promo['id'], $user_id);
        $stmt2->execute();
        $used = $stmt2->get_result()->fetch_assoc()['total'];
        $stmt2->close();

        if ($used >= $promo['maks_pakai_per_user']) {
            $ket = $promo['maks_pakai_per_user'] == 1
                ? 'Kamu sudah pernah memakai promo ini.'
                : "Kamu sudah memakai promo ini {$promo['maks_pakai_per_user']}x (batas tercapai).";
            return ['valid' => false, 'message' => $ket];
        }
    }

    return [
        'valid'    => true,
        'promo_id' => $promo['id'],
        'diskon'   => $promo['diskon_persen'],
        'judul'    => $promo['judul'],
    ];
}

$user_id       = $_SESSION['id'];
$listing_id    = intval($_POST['listing_id']    ?? 0);
$room_id       = intval($_POST['room_id']       ?? 0) ?: null;
$checkin       = trim($_POST['checkin']         ?? '');
$checkout      = trim($_POST['checkout']        ?? '');
$jumlah_tamu   = intval($_POST['jumlah_tamu']   ?? 1);
$metode        = trim($_POST['metode_bayar']    ?? 'gopay');
$kode_promo    = strtoupper(trim($_POST['kode_promo'] ?? ''));
$waktu_bayar   = trim($_POST['waktu_bayar']     ?? 'now');
$tipe_bayar    = $waktu_bayar === 'later' ? 'dp' : 'lunas';
$no_hp         = trim($_POST['no_hp']           ?? '');
$nama_kartu    = trim($_POST['nama_kartu']      ?? '');
$nomor_kartu   = preg_replace('/\s+/', '', trim($_POST['nomor_kartu']   ?? ''));
$expired_kartu = trim($_POST['expired_kartu']   ?? '');
$cvv           = trim($_POST['cvv']             ?? '');

$redirect_params = http_build_query([
    'listing_id'  => $listing_id,
    'room_id'     => $room_id,
    'checkin'     => $checkin,
    'checkout'    => $checkout,
    'jumlah_tamu' => $jumlah_tamu,
    'promo'       => $kode_promo,
]);

if (!$listing_id || !$checkin || !$checkout) {
    $_SESSION['booking_error'] = 'Data tidak lengkap.';
    header("Location: payment_confirm.php?$redirect_params");
    exit;
}

$tgl_in  = DateTime::createFromFormat('Y-m-d', $checkin);
$tgl_out = DateTime::createFromFormat('Y-m-d', $checkout);
if (!$tgl_in || !$tgl_out || $tgl_out <= $tgl_in) {
    $_SESSION['booking_error'] = 'Tanggal tidak valid.';
    header("Location: payment_confirm.php?$redirect_params");
    exit;
}
$jumlah_malam = (int)$tgl_in->diff($tgl_out)->days;

if ($room_id) {
    $stmt = $koneksi->prepare("SELECT harga_malam FROM listing_rooms WHERE id = ? AND listing_id = ? LIMIT 1");
    $stmt->bind_param('ii', $room_id, $listing_id);
} else {
    $stmt = $koneksi->prepare("SELECT harga_malam FROM listings WHERE id = ? AND status = 'aktif' LIMIT 1");
    $stmt->bind_param('i', $listing_id);
}
$stmt->execute();
$row_harga = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row_harga) {
    $_SESSION['booking_error'] = 'Penginapan tidak ditemukan.';
    header("Location: payment_confirm.php?$redirect_params");
    exit;
}

$harga_malam = (float)$row_harga['harga_malam'];
$subtotal    = $harga_malam * $jumlah_malam;
$diskon_amt  = 0;
$promo_id    = null;

if ($kode_promo) {
    $cek = validatePromo($koneksi, $kode_promo, $user_id, $jumlah_malam);
    if (!$cek['valid']) {
        $_SESSION['booking_error'] = $cek['message'];
        header("Location: payment_confirm.php?$redirect_params");
        exit;
    }
    $promo_id   = $cek['promo_id'];
    $diskon_amt = $subtotal * ($cek['diskon'] / 100);
}

$biaya_layanan = (int)round(($subtotal - $diskon_amt) * 0.05);
$total_harga   = $subtotal - $diskon_amt + $biaya_layanan;

$dp_amount  = $tipe_bayar === 'dp' ? (int)round($total_harga * 0.30) : $total_harga;
$sisa_bayar = $total_harga - $dp_amount;

$cek_overlap = $koneksi->prepare("
    SELECT id FROM bookings
    WHERE listing_id = ?
      AND status NOT IN ('dibatalkan')
      AND checkin  < ?
      AND checkout > ?
    LIMIT 1
");
$cek_overlap->bind_param('iss', $listing_id, $checkout, $checkin);
$cek_overlap->execute();
$cek_overlap->store_result();

if ($cek_overlap->num_rows > 0) {
    $cek_overlap->close();
    $_SESSION['booking_error'] = 'Tanggal sudah dipesan. Pilih tanggal lain.';
    header("Location: payment_confirm.php?$redirect_params");
    exit;
}
$cek_overlap->close();

$stmt = $koneksi->prepare("
    INSERT INTO bookings
        (listing_id, user_id, room_id, checkin, checkout,
         jumlah_tamu, total_harga, dp_amount, sisa_bayar, tipe_bayar,
         status, metode_bayar, kode_promo)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu', ?, ?)
");
$stmt->bind_param('iiissiddiiss',
    $listing_id, $user_id, $room_id,
    $checkin, $checkout,
    $jumlah_tamu, $total_harga,
    $dp_amount, $sisa_bayar, $tipe_bayar,
    $metode, $kode_promo
);

if ($stmt->execute()) {
    $booking_id = $koneksi->insert_id;
    $stmt->close();

    $jumlah_trx = $dp_amount;
    $trx = $koneksi->prepare("
        INSERT INTO transactions
            (booking_id, jumlah, metode, no_hp, nama_kartu,
             nomor_kartu, expired_kartu, cvv, status, dibayar_pada)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'sukses', NOW())
    ");
    $trx->bind_param(
        'idssssss',
        $booking_id,
        $jumlah_trx,
        $metode,
        $no_hp         ?: null,
        $nama_kartu    ?: null,
        $nomor_kartu   ?: null,
        $expired_kartu ?: null,
        $cvv           ?: null
    );
    $trx->execute();
    $trx->close();

    if ($promo_id) {
        $pu = $koneksi->prepare("INSERT INTO promo_usage (promo_id, user_id, booking_id) VALUES (?, ?, ?)");
        $pu->bind_param('iii', $promo_id, $user_id, $booking_id);
        $pu->execute();
        $pu->close();

        $up = $koneksi->prepare("UPDATE promo_codes SET sudah_dipakai = sudah_dipakai + 1 WHERE id = ?");
        $up->bind_param('i', $promo_id);
        $up->execute();
        $up->close();
    }

    $_SESSION['last_booking_id'] = $booking_id;
    $koneksi->close();
    header('Location: payment_success.php');
    exit;

} else {
    $stmt->close();
    $koneksi->close();
    $_SESSION['booking_error'] = 'Gagal menyimpan reservasi. Coba lagi.';
    header("Location: payment_confirm.php?$redirect_params");
    exit;
}