<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_host.php';
require_once '../../../koneksi.php';

$hostId = (int) ($_SESSION['id'] ?? 0);
$listingId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$flashMsg = '';
$flashType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_aksi'])) {
    $aksi = $_POST['_aksi'];

    if ($aksi === 'hapus_foto') {
        header('Content-Type: application/json');
        $photoId = (int) ($_POST['photo_id'] ?? 0);
        $chk = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT lp.id, lp.nama_file FROM listing_photos lp
             JOIN listings l ON l.id = lp.listing_id
             WHERE lp.id = $photoId AND l.host_id = $hostId LIMIT 1"
        ));
        if (!$chk) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ditemukan']);
            exit;
        }
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/listings/' . $chk['nama_file'];
        if (file_exists($filePath))
            @unlink($filePath);
        mysqli_query($koneksi, "DELETE FROM listing_photos WHERE id = $photoId");
        echo json_encode(['status' => 'ok']);
        exit;
    }

    if ($aksi === 'hapus_room') {
        header('Content-Type: application/json');
        $roomId = (int) ($_POST['room_id'] ?? 0);
        $chk = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT lr.id, lr.foto FROM listing_rooms lr
             JOIN listings l ON l.id = lr.listing_id
             WHERE lr.id = $roomId AND l.host_id = $hostId LIMIT 1"
        ));
        if (!$chk) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ditemukan']);
            exit;
        }
        if (!empty($chk['foto'])) {
            $fotoPath = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/rooms/' . $chk['foto'];
            if (file_exists($fotoPath))
                @unlink($fotoPath);
        }
        mysqli_query($koneksi, "DELETE FROM listing_rooms WHERE id = $roomId");
        echo json_encode(['status' => 'ok']);
        exit;
    }

    if ($aksi === 'simpan_room') {
        header('Content-Type: application/json');
        $roomId = (int) ($_POST['room_id'] ?? 0);
        $nama = mysqli_real_escape_string($koneksi, trim($_POST['nama'] ?? ''));
        $deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));
        $ukuran = ($_POST['ukuran_m2'] !== '') ? (int) $_POST['ukuran_m2'] : null;
        $maxTamu = max(1, (int) ($_POST['max_tamu'] ?? 1));
        $harga = (float) ($_POST['harga_malam'] ?? 0);
        $fasilitas = json_encode($_POST['fasilitas'] ?? []);
        $urutan = (int) ($_POST['urutan'] ?? 0);
        $ukuranSQL = ($ukuran !== null) ? $ukuran : 'NULL';

        if (!$nama || $harga <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Nama dan harga wajib diisi.']);
            exit;
        }

        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/rooms/';
        $namaFoto = '';

        if ($roomId > 0) {
            $existing = mysqli_fetch_assoc(mysqli_query(
                $koneksi,
                "SELECT lr.id, lr.foto FROM listing_rooms lr
                 JOIN listings l ON l.id = lr.listing_id
                 WHERE lr.id = $roomId AND l.host_id = $hostId LIMIT 1"
            ));
            if (!$existing) {
                echo json_encode(['status' => 'error', 'message' => 'Kamar tidak ditemukan.']);
                exit;
            }
            $namaFoto = $existing['foto'] ?? '';
        }

        if (!empty($_FILES['foto']['tmp_name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'jfif'])) {
                $newFile = 'room_' . ($listingId ?: 'new') . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $newFile)) {
                    if ($roomId > 0 && !empty($namaFoto)) {
                        $oldPath = $uploadDir . $namaFoto;
                        if (file_exists($oldPath))
                            @unlink($oldPath);
                    }
                    $namaFoto = $newFile;
                }
            }
        }

        $namaFotoSQL = mysqli_real_escape_string($koneksi, $namaFoto);
        $fasilitasSQL = mysqli_real_escape_string($koneksi, $fasilitas);

        if ($roomId > 0) {
            mysqli_query($koneksi, "UPDATE listing_rooms SET
                nama        = '$nama',
                deskripsi   = '$deskripsi',
                ukuran_m2   = $ukuranSQL,
                max_tamu    = $maxTamu,
                harga_malam = $harga,
                fasilitas   = '$fasilitasSQL',
                foto        = '$namaFotoSQL',
                urutan      = $urutan
              WHERE id = $roomId");
            echo json_encode(['status' => 'ok', 'room_id' => $roomId, 'foto' => $namaFoto]);
        } else {
            $lid = $listingId;
            mysqli_query($koneksi, "INSERT INTO listing_rooms
                (listing_id, nama, deskripsi, ukuran_m2, max_tamu, harga_malam, fasilitas, foto, urutan)
              VALUES
                ($lid, '$nama', '$deskripsi', $ukuranSQL, $maxTamu, $harga, '$fasilitasSQL', '$namaFotoSQL', $urutan)");
            $newId = (int) mysqli_insert_id($koneksi);
            echo json_encode(['status' => 'ok', 'room_id' => $newId, 'foto' => $namaFoto]);
        }
        exit;
    }

    if ($aksi === 'simpan') {
        $judul = mysqli_real_escape_string($koneksi, trim($_POST['judul'] ?? ''));
        $tipe = mysqli_real_escape_string($koneksi, $_POST['tipe_properti'] ?? '');
        $lokasi = mysqli_real_escape_string($koneksi, trim($_POST['lokasi'] ?? ''));
        $deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));
        $max_tamu = (int) ($_POST['max_tamu'] ?? 2);
        $kamar_tidur = (int) ($_POST['kamar_tidur'] ?? 1);
        $kamar_mandi = (int) ($_POST['kamar_mandi'] ?? 1);
        $harga_malam = (float) ($_POST['harga_malam'] ?? 0);
        $harga_akhir = (isset($_POST['harga_akhir_pekan']) && $_POST['harga_akhir_pekan'] !== '')
            ? (float) $_POST['harga_akhir_pekan'] : null;
        $min_malam = (int) ($_POST['min_malam'] ?? 1);
        $kebijakan = mysqli_real_escape_string($koneksi, $_POST['kebijakan_pembatalan'] ?? 'fleksibel');
        $checkin = mysqli_real_escape_string($koneksi, $_POST['jam_checkin'] ?? '14:00');
        $checkout = mysqli_real_escape_string($koneksi, $_POST['jam_checkout'] ?? '12:00');
        $tipe_booking = mysqli_real_escape_string($koneksi, $_POST['tipe_booking'] ?? 'permintaan');
        $status = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'draft');
        $amenitasNama = $_POST['amenitas'] ?? [];
        $boleh_hewan = (int) ($_POST['boleh_hewan'] ?? 0);
        $boleh_merokok = (int) ($_POST['boleh_merokok'] ?? 0);
        $boleh_anak = (int) ($_POST['boleh_anak'] ?? 1);
        $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan_tambahan'] ?? '');

        if (!$judul || !$tipe || !$lokasi) {
            $flashMsg = 'Judul, tipe, dan lokasi wajib diisi.';
            $flashType = 'error';
        } else {
            $hargaAkhirSQL = ($harga_akhir !== null) ? $harga_akhir : 'NULL';

            if ($listingId) {
                mysqli_query($koneksi, "UPDATE listings SET
                    judul                = '$judul',
                    tipe_properti        = '$tipe',
                    lokasi               = '$lokasi',
                    deskripsi            = '$deskripsi',
                    max_tamu             = $max_tamu,
                    kamar_tidur          = $kamar_tidur,
                    kamar_mandi          = $kamar_mandi,
                    harga_malam          = $harga_malam,
                    harga_akhir_pekan    = $hargaAkhirSQL,
                    min_malam            = $min_malam,
                    kebijakan_pembatalan = '$kebijakan',
                    jam_checkin          = '$checkin',
                    jam_checkout         = '$checkout',
                    tipe_booking         = '$tipe_booking',
                    status               = '$status'
                  WHERE id = $listingId AND host_id = $hostId");
            } else {
                mysqli_query($koneksi, "INSERT INTO listings
                    (host_id, judul, tipe_properti, lokasi, deskripsi,
                     max_tamu, kamar_tidur, kamar_mandi,
                     harga_malam, harga_akhir_pekan,
                     min_malam, kebijakan_pembatalan,
                     jam_checkin, jam_checkout, tipe_booking, status, dibuat_pada)
                  VALUES
                    ($hostId, '$judul', '$tipe', '$lokasi', '$deskripsi',
                     $max_tamu, $kamar_tidur, $kamar_mandi,
                     $harga_malam, $hargaAkhirSQL,
                     $min_malam, '$kebijakan',
                     '$checkin', '$checkout', '$tipe_booking', '$status', NOW())");
                $listingId = (int) mysqli_insert_id($koneksi);
            }

            mysqli_query($koneksi, "DELETE FROM listing_amenities WHERE listing_id = $listingId");
            foreach ($amenitasNama as $nama) {
                $nama = mysqli_real_escape_string($koneksi, trim($nama));
                if ($nama === '')
                    continue;
                mysqli_query($koneksi, "INSERT INTO listing_amenities (listing_id, nama_fasilitas) VALUES ($listingId, '$nama')");
            }

            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/listings/';
            if (!empty($_FILES['new_photos']['name'][0])) {
                $hasCover = mysqli_fetch_assoc(mysqli_query(
                    $koneksi,
                    "SELECT id FROM listing_photos WHERE listing_id = $listingId AND adalah_cover = 1 LIMIT 1"
                ));
                $firstPhoto = !$hasCover;
                foreach ($_FILES['new_photos']['tmp_name'] as $i => $tmp) {
                    if (!$tmp || $_FILES['new_photos']['error'][$i] !== UPLOAD_ERR_OK)
                        continue;
                    $ext = strtolower(pathinfo($_FILES['new_photos']['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp']))
                        continue;
                    $namaFile = 'listing_' . $listingId . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmp, $uploadDir . $namaFile)) {
                        $isCover = ($firstPhoto && $i === 0) ? 1 : 0;
                        mysqli_query($koneksi, "INSERT INTO listing_photos (listing_id, nama_file, adalah_cover) VALUES ($listingId, '$namaFile', $isCover)");
                    }
                }
            }

            $kebijakan_map = [
                'fleksibel' => 'Gratis hingga 24 jam sebelum check-in',
                'moderat' => 'Refund 50% jika dibatalkan 5 hari sebelum check-in',
                'ketat' => 'Tidak ada refund setelah konfirmasi',
            ];
            $kebijakan_pol = mysqli_real_escape_string($koneksi, $kebijakan_map[$kebijakan] ?? $kebijakan);
            $checkin_pol = mysqli_real_escape_string($koneksi, $checkin);
            $checkout_pol = mysqli_real_escape_string($koneksi, $checkout);

            $existPol = mysqli_fetch_assoc(mysqli_query(
                $koneksi,
                "SELECT id FROM listing_policies WHERE listing_id = $listingId LIMIT 1"
            ));
            if ($existPol) {
                mysqli_query($koneksi, "UPDATE listing_policies SET
                    jam_checkin          = '$checkin_pol',
                    jam_checkout         = '$checkout_pol',
                    kebijakan_pembatalan = '$kebijakan_pol',
                    boleh_hewan          = $boleh_hewan,
                    boleh_merokok        = $boleh_merokok,
                    boleh_anak           = $boleh_anak,
                    catatan_tambahan     = '$catatan'
                  WHERE listing_id = $listingId");
            } else {
                mysqli_query($koneksi, "INSERT INTO listing_policies
                    (listing_id, jam_checkin, jam_checkout, kebijakan_pembatalan,
                     boleh_hewan, boleh_merokok, boleh_anak, catatan_tambahan)
                  VALUES
                    ($listingId, '$checkin_pol', '$checkout_pol', '$kebijakan_pol',
                     $boleh_hewan, $boleh_merokok, $boleh_anak, '$catatan')");
            }

            header("Location: listing_detail.php?id=$listingId&saved=1");
            exit;
        }
    }
}

$listing = null;
$photos = [];
$currentAmenitas = [];
$policies = null;
$existingRooms = [];

$masterAmenitas = [
    'Wi-Fi',
    'TV',
    'AC / Pendingin Ruangan',
    'Dapur',
    'Mesin Cuci',
    'Parkir Gratis',
    'Kolam Renang',
    'Kotak P3K',
    'Alat Pemadam',
    'Shower Air Panas',
    'Ruang Kerja',
    'Ramah Hewan Peliharaan',
];

$fasilitasKamarMaster = [
    'Kasur Twin' => 'ph-bed',
    'Kasur Double' => 'ph-bed',
    'Kasur Queen' => 'ph-bed',
    'Kasur King' => 'ph-bed',
    'Kamar Mandi Dalam' => 'ph-shower',
    'Bathtub' => 'ph-bathtub',
    'TV LED' => 'ph-television',
    'Minibar' => 'ph-wine',
    'Balkon' => 'ph-door-open',
    'AC' => 'ph-snowflake',
    'Brankas' => 'ph-lock-key',
    'Meja Kerja' => 'ph-desk',
    'Sofa' => 'ph-armchair',
];

if ($listingId) {
    $q = mysqli_query($koneksi, "SELECT * FROM listings WHERE id = $listingId AND host_id = $hostId LIMIT 1");
    $listing = mysqli_fetch_assoc($q);
    if (!$listing) {
        header('Location: listing.php');
        exit;
    }

    $qp = mysqli_query($koneksi, "SELECT id, nama_file, adalah_cover FROM listing_photos WHERE listing_id = $listingId ORDER BY adalah_cover DESC, id ASC");
    while ($r = mysqli_fetch_assoc($qp))
        $photos[] = $r;

    $qa = mysqli_query($koneksi, "SELECT nama_fasilitas FROM listing_amenities WHERE listing_id = $listingId");
    while ($r = mysqli_fetch_assoc($qa))
        $currentAmenitas[] = $r['nama_fasilitas'];

    $qpol = mysqli_query($koneksi, "SELECT * FROM listing_policies WHERE listing_id = $listingId LIMIT 1");
    $policies = mysqli_fetch_assoc($qpol);

    $qr = mysqli_query($koneksi, "SELECT * FROM listing_rooms WHERE listing_id = $listingId ORDER BY urutan ASC, id ASC");
    while ($r = mysqli_fetch_assoc($qr))
        $existingRooms[] = $r;
}

$isEdit = !empty($listing);
$pageTitle = $isEdit ? 'Edit Listing' : 'Tambah Listing';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $pageTitle ?> — Teman Singgah</title>
    <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />
    <link rel="stylesheet" href="../../../components/root.css" />
    <link rel="stylesheet" href="../../../components/navbar.css" />
    <link rel="stylesheet" href="../../../components/footer.css" />
    <link rel="stylesheet" href="../../../popups/auth.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
    <style>
        .edit-wrap {
            max-width: 780px;
            margin: 0 auto;
            margin-top: 100px;
            padding: 0 var(--space-24) var(--space-96);
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: var(--color-text-secondary);
            margin-bottom: var(--space-24);
        }

        .breadcrumb a {
            color: var(--color-primary);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .page-header {
            margin-bottom: var(--space-40);
        }

        .page-header h1 {
            font-size: var(--text-3xl);
            font-weight: var(--font-bold);
            color: var(--color-text-primary);
            font-family: 'Inter', sans-serif;
            margin-bottom: var(--space-8);
        }

        .page-header p {
            color: var(--color-text-secondary);
            font-size: 0.9375rem;
        }

        .form-section {
            background: var(--color-bg-card, #fff);
            border: 1px solid var(--color-border-subtle);
            border-radius: var(--radius-3xl);
            padding: var(--space-32);
            margin-bottom: var(--space-24);
        }

        .form-section-title {
            display: flex;
            align-items: center;
            gap: var(--space-12);
            font-size: var(--text-lg);
            font-weight: var(--font-bold);
            color: var(--color-text-primary);
            margin-bottom: var(--space-24);
            padding-bottom: var(--space-16);
            border-bottom: 1px solid var(--color-border-subtle);
        }

        .form-section-title .section-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-lg);
            background: var(--color-primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary);
            font-size: 1rem;
            flex-shrink: 0;
        }

        .section-icon i {
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .form-group {
            margin-bottom: var(--space-20);
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--color-text-primary);
            margin-bottom: var(--space-8);
        }

        .form-label .required {
            color: #dc2626;
            margin-left: 2px;
        }

        .form-hint {
            font-size: 0.775rem;
            color: var(--color-text-secondary);
            margin-top: 5px;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--color-border-strong, #d1d5db);
            border-radius: var(--radius-xl);
            font-size: 0.9375rem;
            color: var(--color-text-primary);
            background: var(--color-bg, #fff);
            transition: border-color 0.15s, box-shadow 0.15s;
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb, 99, 102, 241), 0.12);
        }

        .form-select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 16px;
            padding-right: 40px;
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }

        .form-row {
            display: grid;
            gap: var(--space-16);
        }

        .form-row.cols-2 {
            grid-template-columns: 1fr 1fr;
        }

        .form-row.cols-3 {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .stepper {
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }

        .stepper-btn {
            width: 36px;
            height: 36px;
            min-width: 36px;
            min-height: 36px;
            border-radius: 50%;
            border: 1.5px solid var(--color-border-strong);
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--color-text-primary);
            transition: background 0.1s;
            flex-shrink: 0;
            padding: 0;
            line-height: 1;
        }

        .stepper-btn:hover {
            background: var(--color-border-subtle);
        }

        .stepper-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .stepper-val {
            font-size: 1rem;
            font-weight: 600;
            min-width: 24px;
            text-align: center;
            color: var(--color-text-primary);
        }

        .stepper-wrap {
            display: flex;
            align-items: center;
            height: 46px;
        }

        .facility-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: var(--space-10);
        }

        .facility-check {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border: 1.5px solid var(--color-border-subtle);
            border-radius: var(--radius-xl);
            cursor: pointer;
            font-size: 0.875rem;
            color: var(--color-text-primary);
            transition: border-color 0.15s, background 0.15s;
            user-select: none;
        }

        .facility-check:hover {
            border-color: var(--color-primary);
            background: var(--color-primary-light);
        }

        .facility-check.selected {
            border-color: var(--color-primary);
            background: var(--color-primary-light);
            font-weight: 600;
        }

        .facility-check input[type="checkbox"] {
            display: none;
        }

        .facility-check .check-icon {
            width: 18px;
            height: 18px;
            border-radius: 5px;
            border: 1.5px solid var(--color-border-strong);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.7rem;
            color: transparent;
            transition: all 0.15s;
        }

        .facility-check.selected .check-icon {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: #fff;
        }

        .toggle-options {
            display: flex;
            gap: var(--space-8);
        }

        .toggle-opt {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            padding: 10px 8px;
            border: 1.5px solid var(--color-border-subtle);
            border-radius: var(--radius-xl);
            cursor: pointer;
            font-size: 0.8rem;
            color: var(--color-text-secondary);
            transition: all 0.15s;
            text-align: center;
            user-select: none;
        }

        .toggle-opt input {
            display: none;
        }

        .toggle-opt i {
            font-size: 1.2rem;
        }

        .toggle-opt:hover {
            border-color: var(--color-primary);
            color: var(--color-primary);
        }

        .toggle-opt.selected-yes {
            border-color: #16a34a;
            background: #f0fdf4;
            color: #16a34a;
            font-weight: 600;
        }

        .toggle-opt.selected-no {
            border-color: #dc2626;
            background: #fef2f2;
            color: #dc2626;
            font-weight: 600;
        }

        .photo-upload-area {
            border: 2px dashed var(--color-border-strong);
            border-radius: var(--radius-2xl);
            padding: var(--space-32);
            text-align: center;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            margin-bottom: var(--space-16);
        }

        .photo-upload-area:hover {
            border-color: var(--color-primary);
            background: var(--color-primary-light);
        }

        .photo-upload-area i {
            font-size: 2rem;
            color: var(--color-text-secondary);
            margin-bottom: 8px;
            display: block;
        }

        .photo-upload-area p {
            font-size: 0.875rem;
            color: var(--color-text-secondary);
            margin: 0;
        }

        #photoInput {
            display: none;
        }

        .photo-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: var(--space-12);
        }

        .photo-preview-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: var(--radius-xl);
            overflow: hidden;
            border: 2px solid transparent;
            background: var(--color-border-subtle);
        }

        .photo-preview-item.is-cover {
            border-color: var(--color-primary);
        }

        .photo-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-cover-badge {
            position: absolute;
            bottom: 5px;
            left: 5px;
            background: var(--color-primary);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: var(--radius-full);
            text-transform: uppercase;
        }

        .photo-remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.55);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            opacity: 0;
            transition: opacity 0.15s;
        }

        .photo-preview-item:hover .photo-remove-btn {
            opacity: 1;
        }

        .status-options {
            display: flex;
            gap: var(--space-12);
        }

        .status-opt {
            flex: 1;
            padding: 14px var(--space-16);
            border: 2px solid var(--color-border-subtle);
            border-radius: var(--radius-2xl);
            cursor: pointer;
            text-align: center;
            transition: all 0.15s;
        }

        .status-opt.selected-aktif {
            border-color: #16a34a;
            background: #f0fdf4;
        }

        .status-opt.selected-draft {
            border-color: #ca8a04;
            background: #fefce8;
        }

        .status-opt.selected-nonaktif {
            border-color: #dc2626;
            background: #fef2f2;
        }

        .status-opt input {
            display: none;
        }

        .status-opt-label {
            font-weight: 700;
            font-size: 0.875rem;
            color: var(--color-text-primary);
            margin-bottom: 3px;
        }

        .status-opt-desc {
            font-size: 0.775rem;
            color: var(--color-text-secondary);
        }

        .submit-bar {
            position: sticky;
            bottom: 24px;
            display: flex;
            justify-content: flex-end;
            gap: var(--space-12);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            border: 1px solid var(--color-border-subtle);
            border-radius: var(--radius-2xl);
            padding: var(--space-16) var(--space-24);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 11px 20px;
            border: 1.5px solid var(--color-border-strong);
            border-radius: var(--radius-xl);
            background: transparent;
            color: var(--color-text-primary);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }

        .btn-outline:hover {
            background: var(--color-border-subtle);
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 11px 24px;
            border-radius: var(--radius-xl);
            background: var(--color-primary);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: background 0.15s;
        }

        .btn-primary:hover {
            background: var(--color-primary-hover);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .flash-msg {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border-radius: var(--radius-2xl);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: var(--space-24);
        }

        .flash-msg.error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .flash-msg.success {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .ts-toast {
            position: fixed;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%) translateY(12px);
            background: #111827;
            color: #fff;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 11px 20px;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s, transform 0.2s;
            z-index: 99999;
        }

        .ts-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .ts-toast.success {
            background: #15803d;
        }

        .ts-toast.error {
            background: #dc2626;
        }

        .input-prefix {
            position: relative;
        }

        .input-prefix-text {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.875rem;
            color: var(--color-text-secondary);
            pointer-events: none;
            font-weight: 500;
        }

        .input-prefix .form-input {
            padding-left: 44px;
        }

        /* ── Rooms list ── */
        .rooms-list-edit {
            display: flex;
            flex-direction: column;
            gap: var(--space-12);
            margin-bottom: var(--space-20);
        }

        .room-entry-edit {
            display: flex;
            align-items: center;
            gap: var(--space-12);
            padding: var(--space-14) var(--space-16);
            border: 1px solid var(--color-border-subtle);
            border-radius: var(--radius-xl);
            background: var(--color-bg-card, #fff);
        }

        .room-entry-thumb {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-lg);
            overflow: hidden;
            flex-shrink: 0;
            background: var(--color-bg-skeleton);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .room-entry-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .room-entry-thumb i {
            font-size: 1.4rem;
            color: var(--color-border-strong);
        }

        .room-entry-info-edit {
            flex: 1;
            min-width: 0;
        }

        .room-entry-name-edit {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--color-text-primary);
            display: block;
            margin-bottom: 2px;
        }

        .room-entry-meta-edit {
            font-size: 0.775rem;
            color: var(--color-text-secondary);
        }

        .room-entry-btns {
            display: flex;
            gap: var(--space-8);
            flex-shrink: 0;
        }

        .room-edit-btn-small {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 12px;
            border: 1.5px solid var(--color-border-strong);
            border-radius: var(--radius-lg);
            background: transparent;
            color: var(--color-text-primary);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }

        .room-edit-btn-small:hover {
            background: var(--color-border-subtle);
        }

        .room-delete-btn-small {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 1.5px solid #fecaca;
            border-radius: var(--radius-lg);
            background: #fef2f2;
            color: #dc2626;
            cursor: pointer;
            transition: background 0.15s;
        }

        .room-delete-btn-small:hover {
            background: #fee2e2;
        }

        .add-room-btn-edit {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border: 2px dashed var(--color-border-strong);
            border-radius: var(--radius-xl);
            background: transparent;
            color: var(--color-text-secondary);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: border-color 0.15s, color 0.15s, background 0.15s;
            width: 100%;
            justify-content: center;
        }

        .add-room-btn-edit:hover {
            border-color: var(--color-primary);
            color: var(--color-primary);
            background: var(--color-primary-light);
        }

        /* ── Room modal ── */
        .room-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 9000;
            align-items: center;
            justify-content: center;
            padding: var(--space-16);
        }

        .room-modal-overlay.open {
            display: flex;
        }

        .room-modal-box {
            background: #fff;
            border-radius: 20px;
            width: 100%;
            max-width: 660px;
            max-height: 92vh;
            overflow-y: auto;
            box-shadow: 0 16px 56px rgba(0, 0, 0, 0.22);
        }

        .room-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--color-border-subtle);
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1;
        }

        .room-modal-header h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-text-primary);
            margin: 0;
        }

        .room-modal-close {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1.5px solid var(--color-border-strong);
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-text-secondary);
            font-size: 0.8rem;
            transition: background 0.15s;
        }

        .room-modal-close:hover {
            background: var(--color-border-subtle);
        }

        .room-modal-body {
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .room-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 16px 24px;
            border-top: 1px solid var(--color-border-subtle);
        }

        /* foto + fields row */
        .rm-top-row {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .rm-foto-wrap {
            width: 150px;
            min-width: 150px;
            height: 108px;
            border-radius: 12px;
            border: 1.5px dashed var(--color-border-strong);
            cursor: pointer;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: var(--color-bg, #f9fafb);
            transition: border-color 0.15s, background 0.15s;
            flex-shrink: 0;
        }

        .rm-foto-wrap:hover {
            border-color: var(--color-primary);
            background: var(--color-primary-light);
        }

        .rm-foto-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            color: var(--color-text-secondary);
            font-size: 0.75rem;
            text-align: center;
            padding: 8px;
        }

        .rm-foto-placeholder i {
            font-size: 1.6rem;
        }

        .rm-foto-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            inset: 0;
        }

        .rm-foto-remove {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.55);
            color: #fff;
            border: none;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            z-index: 2;
        }

        .rm-fields-right {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* field helpers inside modal */
        .rm-field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .rm-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .rm-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--color-text-secondary);
        }

        .rm-input {
            padding: 9px 12px;
            border: 1.5px solid var(--color-border-strong, #d1d5db);
            border-radius: 10px;
            font-size: 0.875rem;
            color: var(--color-text-primary);
            background: var(--color-bg, #fff);
            font-family: 'Inter', sans-serif;
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.15s;
        }

        .rm-input:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .rm-prefix {
            position: relative;
        }

        .rm-prefix-text {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8rem;
            color: var(--color-text-secondary);
            pointer-events: none;
            font-weight: 500;
        }

        .rm-prefix .rm-input {
            padding-left: 36px;
        }

        .rm-stepper {
            display: flex;
            align-items: center;
            gap: 10px;
            height: 38px;
        }

        .rm-stepper-btn {
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 50%;
            border: 1.5px solid var(--color-border-strong);
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: var(--color-text-primary);
            line-height: 1;
            padding: 0;
        }

        .rm-stepper-btn:hover {
            background: var(--color-border-subtle);
        }

        .rm-stepper-val {
            font-size: 0.9375rem;
            font-weight: 600;
            min-width: 22px;
            text-align: center;
        }

        .rm-textarea {
            padding: 9px 12px;
            border: 1.5px solid var(--color-border-strong, #d1d5db);
            border-radius: 10px;
            font-size: 0.875rem;
            color: var(--color-text-primary);
            background: var(--color-bg, #fff);
            font-family: 'Inter', sans-serif;
            width: 100%;
            box-sizing: border-box;
            resize: none;
            line-height: 1.55;
            transition: border-color 0.15s;
        }

        .rm-textarea:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .rm-section-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--color-text-secondary);
            margin-bottom: 8px;
            display: block;
        }

        /* fasilitas chips inside modal */
        .room-facilities-grid-edit {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(144px, 1fr));
            gap: 6px;
        }

        .facility-chip-edit {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 7px 11px;
            border: 1.5px solid var(--color-border-subtle);
            border-radius: 9px;
            cursor: pointer;
            font-size: 0.8125rem;
            color: var(--color-text-primary);
            transition: border-color 0.15s, background 0.15s;
            user-select: none;
        }

        .facility-chip-edit input {
            display: none;
        }

        .facility-chip-edit i {
            color: var(--color-primary);
            font-size: 0.875rem;
        }

        .facility-chip-edit:hover {
            border-color: var(--color-primary);
            background: var(--color-primary-light);
        }

        .facility-chip-edit.checked {
            border-color: var(--color-primary);
            background: var(--color-primary-light);
            font-weight: 600;
        }

        .form-error-inline {
            font-size: 0.8125rem;
            color: #dc2626;
            margin-top: 4px;
            display: none;
        }

        @media (max-width: 640px) {

            .form-row.cols-2,
            .form-row.cols-3 {
                grid-template-columns: 1fr;
            }

            .status-options {
                flex-direction: column;
            }

            .rm-top-row {
                flex-direction: column;
            }

            .rm-foto-wrap {
                width: 100%;
                height: 140px;
            }

            .rm-field-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header class="navbar">
        <nav class="navbar-container">
            <a href="reservations.php" class="logo-link"></a>
            <div class="logo-section">
                <img src="../../../assets/logo/logo_temansinggah.svg" alt="Logo" class="logo-icon" />
                <img src="../../../assets/logo/label_temansinggah.svg" alt="Teman Singgah" class="logo-name" />
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="reservations.php" class="nav-link">Reservasi</a></li>
                <li class="nav-item"><a href="calendar_router.php" class="nav-link">Kalender</a></li>
                <li class="nav-item"><a href="listing.php" class="nav-link active">Listing</a></li>
                <div class="nav-indicator"></div>
            </ul>
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/components/navbar_profile_host.php'; ?>
        </nav>
    </header>

    <main class="edit-wrap">

        <nav class="breadcrumb">
            <a href="listing.php"><i class="ph-bold ph-house-simple"></i> Listing Saya</a>
            <?php if ($isEdit): ?>
                <i class="ph-bold ph-caret-right"></i>
                <a href="listing_detail.php?id=<?= $listingId ?>"><?= htmlspecialchars($listing['judul']) ?></a>
            <?php endif; ?>
            <i class="ph-bold ph-caret-right"></i>
            <span><?= $isEdit ? 'Edit' : 'Tambah Listing' ?></span>
        </nav>

        <div class="page-header">
            <h1><?= $pageTitle ?></h1>
            <p><?= $isEdit
                ? 'Perbarui informasi listing kamu. Perubahan akan langsung terlihat oleh tamu.'
                : 'Isi informasi lengkap untuk listing baru kamu.' ?></p>
        </div>

        <?php if ($flashMsg): ?>
            <div class="flash-msg <?= $flashType ?>">
                <i class="ph-bold <?= $flashType === 'error' ? 'ph-warning-circle' : 'ph-check-circle' ?>"></i>
                <?= htmlspecialchars($flashMsg) ?>
            </div>
        <?php endif; ?>

        <form id="listingForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="_aksi" value="simpan" />
            <input type="hidden" name="id" value="<?= $listingId ?>" />
            <input type="hidden" name="host_id" value="<?= $hostId ?>" />

            <!-- 1. Informasi Dasar -->
            <div class="form-section">
                <div class="form-section-title">
                    <div class="section-icon"><i class="ph-bold ph-house"></i></div>
                    Informasi Dasar
                </div>
                <div class="form-group">
                    <label class="form-label">Judul Listing <span class="required">*</span></label>
                    <input type="text" name="judul" class="form-input"
                        placeholder="cth. Villa Kayu Tropis dengan Kolam Renang Privat"
                        value="<?= htmlspecialchars($listing['judul'] ?? '') ?>" required />
                    <p class="form-hint">Buat judul yang menarik dan deskriptif.</p>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Tipe Properti <span class="required">*</span></label>
                        <select name="tipe_properti" class="form-select" required>
                            <?php
                            $tipes = ['villa', 'rumah', 'apartemen', 'cabin', 'hotel', 'rumah tradisional'];
                            $selTipe = $listing['tipe_properti'] ?? '';
                            foreach ($tipes as $t) {
                                $sel = ($selTipe === $t) ? 'selected' : '';
                                echo "<option value=\"$t\" $sel>" . ucfirst($t) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lokasi / Alamat <span class="required">*</span></label>
                        <input type="text" name="lokasi" class="form-input" placeholder="cth. Ubud, Bali"
                            value="<?= htmlspecialchars($listing['lokasi'] ?? '') ?>" required />
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi <span class="required">*</span></label>
                    <textarea name="deskripsi" class="form-textarea"
                        placeholder="Ceritakan keunikan propertimu kepada tamu..."
                        required><?= htmlspecialchars($listing['deskripsi'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- 2. Kapasitas -->
            <div class="form-section">
                <div class="form-section-title">
                    <div class="section-icon"><i class="ph-bold ph-users"></i></div>
                    Kapasitas
                </div>
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Maks. Tamu</label>
                        <div class="stepper-wrap">
                            <div class="stepper">
                                <button type="button" class="stepper-btn" data-target="max_tamu"
                                    data-op="dec">−</button>
                                <span class="stepper-val"
                                    id="val-max_tamu"><?= (int) ($listing['max_tamu'] ?? 2) ?></span>
                                <input type="hidden" name="max_tamu" id="max_tamu"
                                    value="<?= (int) ($listing['max_tamu'] ?? 2) ?>" />
                                <button type="button" class="stepper-btn" data-target="max_tamu"
                                    data-op="inc">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kamar Tidur</label>
                        <div class="stepper-wrap">
                            <div class="stepper">
                                <button type="button" class="stepper-btn" data-target="kamar_tidur"
                                    data-op="dec">−</button>
                                <span class="stepper-val"
                                    id="val-kamar_tidur"><?= (int) ($listing['kamar_tidur'] ?? 1) ?></span>
                                <input type="hidden" name="kamar_tidur" id="kamar_tidur"
                                    value="<?= (int) ($listing['kamar_tidur'] ?? 1) ?>" />
                                <button type="button" class="stepper-btn" data-target="kamar_tidur"
                                    data-op="inc">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kamar Mandi</label>
                        <div class="stepper-wrap">
                            <div class="stepper">
                                <button type="button" class="stepper-btn" data-target="kamar_mandi"
                                    data-op="dec">−</button>
                                <span class="stepper-val"
                                    id="val-kamar_mandi"><?= (int) ($listing['kamar_mandi'] ?? 1) ?></span>
                                <input type="hidden" name="kamar_mandi" id="kamar_mandi"
                                    value="<?= (int) ($listing['kamar_mandi'] ?? 1) ?>" />
                                <button type="button" class="stepper-btn" data-target="kamar_mandi"
                                    data-op="inc">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Harga -->
            <div class="form-section">
                <div class="form-section-title">
                    <div class="section-icon"><i class="ph-bold ph-currency-circle-dollar"></i></div>
                    Harga
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Harga per Malam <span class="required">*</span></label>
                        <div class="input-prefix">
                            <span class="input-prefix-text">Rp</span>
                            <input type="number" name="harga_malam" class="form-input" min="0" step="1000"
                                placeholder="250000" value="<?= (int) ($listing['harga_malam'] ?? '') ?>" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Akhir Pekan</label>
                        <div class="input-prefix">
                            <span class="input-prefix-text">Rp</span>
                            <input type="number" name="harga_akhir_pekan" class="form-input" min="0" step="1000"
                                placeholder="Kosongkan jika sama"
                                value="<?= $listing['harga_akhir_pekan'] ? (int) $listing['harga_akhir_pekan'] : '' ?>" />
                        </div>
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Min. Malam</label>
                        <div class="stepper-wrap">
                            <div class="stepper">
                                <button type="button" class="stepper-btn" data-target="min_malam"
                                    data-op="dec">−</button>
                                <span class="stepper-val"
                                    id="val-min_malam"><?= (int) ($listing['min_malam'] ?? 1) ?></span>
                                <input type="hidden" name="min_malam" id="min_malam"
                                    value="<?= (int) ($listing['min_malam'] ?? 1) ?>" />
                                <button type="button" class="stepper-btn" data-target="min_malam"
                                    data-op="inc">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe Booking</label>
                        <select name="tipe_booking" class="form-select">
                            <?php
                            $bookingOpts = ['permintaan' => 'Permintaan (perlu konfirmasi)', 'instan' => 'Instan'];
                            $selBooking = $listing['tipe_booking'] ?? 'permintaan';
                            foreach ($bookingOpts as $val => $label) {
                                $sel = ($selBooking === $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $sel>$label</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Kebijakan Pembatalan</label>
                    <select name="kebijakan_pembatalan" class="form-select">
                        <?php
                        $kebijakanOpts = [
                            'fleksibel' => 'Fleksibel — refund penuh jika dibatalkan 24 jam sebelum check-in',
                            'moderat' => 'Moderat — refund 50% jika dibatalkan 5 hari sebelum check-in',
                            'ketat' => 'Ketat — tidak ada refund setelah konfirmasi',
                        ];
                        $selKebijakan = $listing['kebijakan_pembatalan'] ?? 'fleksibel';
                        foreach ($kebijakanOpts as $val => $label) {
                            $sel = ($selKebijakan === $val) ? 'selected' : '';
                            echo "<option value=\"$val\" $sel>$label</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- 4. Jadwal -->
            <div class="form-section">
                <div class="form-section-title">
                    <div class="section-icon"><i class="ph-bold ph-clock"></i></div>
                    Jadwal Check-in &amp; Check-out
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Jam Check-in</label>
                        <input type="time" name="jam_checkin" class="form-input"
                            value="<?= htmlspecialchars(substr($listing['jam_checkin'] ?? '14:00', 0, 5)) ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jam Check-out</label>
                        <input type="time" name="jam_checkout" class="form-input"
                            value="<?= htmlspecialchars(substr($listing['jam_checkout'] ?? '12:00', 0, 5)) ?>" />
                    </div>
                </div>
            </div>

            <!-- 5. Amenitas -->
            <div class="form-section">
                <div class="form-section-title">
                    <div class="section-icon"><i class="ph-bold ph-wifi-high"></i></div>
                    Fasilitas / Amenitas
                </div>
                <div class="facility-grid">
                    <?php foreach ($masterAmenitas as $nama):
                        $checked = in_array($nama, $currentAmenitas);
                        $selClass = $checked ? 'selected' : '';
                        ?>
                        <label class="facility-check <?= $selClass ?>" data-val="<?= htmlspecialchars($nama) ?>">
                            <input type="checkbox" name="amenitas[]" value="<?= htmlspecialchars($nama) ?>" <?= $checked ? 'checked' : '' ?> />
                            <span class="check-icon"><?= $checked ? '&#10003;' : '' ?></span>
                            <?= htmlspecialchars($nama) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 6. Foto -->
            <div class="form-section">
                <div class="form-section-title">
                    <div class="section-icon"><i class="ph-bold ph-images"></i></div>
                    Foto Properti
                </div>
                <?php if (!empty($photos)): ?>
                    <p style="font-size:0.875rem;color:var(--color-text-secondary);margin-bottom:12px;">
                        Foto tersimpan &mdash; hover lalu klik &times; untuk hapus
                    </p>
                    <div class="photo-preview-grid" id="existingPhotos">
                        <?php foreach ($photos as $p):
                            $namaFile = $p['nama_file'];
                            $src = (str_starts_with($namaFile, 'http://') || str_starts_with($namaFile, 'https://'))
                                ? $namaFile
                                : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($namaFile) . '?v=' . time();
                            ?>
                            <div class="photo-preview-item <?= $p['adalah_cover'] ? 'is-cover' : '' ?>"
                                data-photo-id="<?= $p['id'] ?>">
                                <img src="<?= $src ?>" alt="Foto" loading="lazy" />
                                <?php if ($p['adalah_cover']): ?>
                                    <span class="photo-cover-badge">Cover</span>
                                <?php endif; ?>
                                <button type="button" class="photo-remove-btn"
                                    onclick="removeExistingPhoto(<?= $p['id'] ?>, this)" title="Hapus foto">
                                    <i class="ph-bold ph-x"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <hr style="border:none;border-top:1px solid var(--color-border-subtle);margin:20px 0" />
                <?php endif; ?>
                <div class="photo-upload-area" onclick="document.getElementById('photoInput').click()">
                    <i class="ph-bold ph-cloud-arrow-up"></i>
                    <p><strong>Klik untuk upload foto</strong> atau drag &amp; drop</p>
                    <p style="margin-top:4px;font-size:0.775rem;">JPG, PNG, WEBP &mdash; maks 5MB per foto</p>
                </div>
                <input type="file" id="photoInput" name="new_photos[]" accept="image/*" multiple />
                <div class="photo-preview-grid" id="newPhotoPreview"></div>
            </div>

            <!-- 7. Pilihan Kamar -->
            <?php if ($isEdit): ?>
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="section-icon"><i class="ph-bold ph-bed"></i></div>
                        Pilihan Kamar
                    </div>
                    <div class="rooms-list-edit" id="roomsListEdit">
                        <?php foreach ($existingRooms as $room):
                            $fotoSrc = !empty($room['foto'])
                                ? (str_starts_with($room['foto'], 'http')
                                    ? $room['foto']
                                    : '/teman_singgah/assets/uploads/rooms/' . htmlspecialchars($room['foto']) . '?v=' . time())
                                : null;
                            $metaParts = [];
                            if (!empty($room['ukuran_m2']))
                                $metaParts[] = $room['ukuran_m2'] . ' m&sup2;';
                            $metaParts[] = $room['max_tamu'] . ' tamu';
                            $metaParts[] = 'Rp ' . number_format((float) $room['harga_malam'], 0, ',', '.') . '/malam';
                            ?>
                            <div class="room-entry-edit" data-room-id="<?= $room['id'] ?>">
                                <div class="room-entry-thumb">
                                    <?php if ($fotoSrc): ?>
                                        <img src="<?= $fotoSrc ?>" alt="<?= htmlspecialchars($room['nama']) ?>" loading="lazy" />
                                    <?php else: ?>
                                        <i class="ph-bold ph-bed"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="room-entry-info-edit">
                                    <span class="room-entry-name-edit"><?= htmlspecialchars($room['nama']) ?></span>
                                    <span class="room-entry-meta-edit"><?= implode(' &middot; ', $metaParts) ?></span>
                                </div>
                                <div class="room-entry-btns">
                                    <button type="button" class="room-edit-btn-small"
                                        onclick="openRoomModal(<?= htmlspecialchars(json_encode($room), ENT_QUOTES) ?>)">
                                        <i class="ph-bold ph-pencil-simple"></i> Edit
                                    </button>
                                    <button type="button" class="room-delete-btn-small" onclick="deleteRoom(<?= $room['id'] ?>)"
                                        title="Hapus kamar">
                                        <i class="ph-bold ph-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-room-btn-edit" id="btnTambahKamar">
                        <i class="ph-bold ph-plus-circle"></i>
                        Tambah Tipe Kamar
                    </button>
                </div>
            <?php endif; ?>

            <!-- 8. Kebijakan Tambahan -->
            <div class="form-section">
                <div class="form-section-title">
                    <div class="section-icon"><i class="ph-bold ph-shield-check"></i></div>
                    Kebijakan Tambahan
                </div>
                <div class="form-row cols-3">
                    <?php
                    $boleh_hewan_val = (int) ($policies['boleh_hewan'] ?? 0);
                    $boleh_merokok_val = (int) ($policies['boleh_merokok'] ?? 0);
                    $boleh_anak_val = (int) ($policies['boleh_anak'] ?? 1);
                    ?>
                    <div class="form-group">
                        <label class="form-label">Hewan Peliharaan</label>
                        <div class="toggle-options">
                            <label class="toggle-opt <?= $boleh_hewan_val ? 'selected-yes' : '' ?>"
                                data-group="boleh_hewan">
                                <input type="radio" name="boleh_hewan" value="1" <?= $boleh_hewan_val ? 'checked' : '' ?> />
                                <i class="ph-bold ph-paw-print"></i><span>Boleh</span>
                            </label>
                            <label class="toggle-opt <?= !$boleh_hewan_val ? 'selected-no' : '' ?>"
                                data-group="boleh_hewan">
                                <input type="radio" name="boleh_hewan" value="0" <?= !$boleh_hewan_val ? 'checked' : '' ?> />
                                <i class="ph-bold ph-prohibit"></i><span>Tidak</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Merokok</label>
                        <div class="toggle-options">
                            <label class="toggle-opt <?= $boleh_merokok_val ? 'selected-yes' : '' ?>"
                                data-group="boleh_merokok">
                                <input type="radio" name="boleh_merokok" value="1" <?= $boleh_merokok_val ? 'checked' : '' ?> />
                                <i class="ph-bold ph-cigarette"></i><span>Boleh</span>
                            </label>
                            <label class="toggle-opt <?= !$boleh_merokok_val ? 'selected-no' : '' ?>"
                                data-group="boleh_merokok">
                                <input type="radio" name="boleh_merokok" value="0" <?= !$boleh_merokok_val ? 'checked' : '' ?> />
                                <i class="ph-bold ph-cigarette-slash"></i><span>Tidak</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Anak-anak</label>
                        <div class="toggle-options">
                            <label class="toggle-opt <?= $boleh_anak_val ? 'selected-yes' : '' ?>"
                                data-group="boleh_anak">
                                <input type="radio" name="boleh_anak" value="1" <?= $boleh_anak_val ? 'checked' : '' ?> />
                                <i class="ph-bold ph-baby"></i><span>Boleh</span>
                            </label>
                            <label class="toggle-opt <?= !$boleh_anak_val ? 'selected-no' : '' ?>"
                                data-group="boleh_anak">
                                <input type="radio" name="boleh_anak" value="0" <?= !$boleh_anak_val ? 'checked' : '' ?> />
                                <i class="ph-bold ph-prohibit"></i><span>Tidak</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan Tambahan</label>
                    <textarea name="catatan_tambahan" class="form-textarea" rows="3"
                        placeholder="cth. Tamu wajib lapor sebelum kedatangan, tidak menerima tamu rombongan, dll."><?= htmlspecialchars($policies['catatan_tambahan'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- 9. Status -->
            <div class="form-section">
                <div class="form-section-title">
                    <div class="section-icon"><i class="ph-bold ph-toggle-right"></i></div>
                    Status Listing
                </div>
                <div class="status-options">
                    <?php
                    $statuses = [
                        'aktif' => ['Aktif', 'Langsung terlihat oleh tamu'],
                        'draft' => ['Draft', 'Disimpan, belum dipublikasi'],
                        'nonaktif' => ['Nonaktif', 'Disembunyikan sementara'],
                    ];
                    $curStatus = $listing['status'] ?? 'draft';
                    foreach ($statuses as $val => [$label, $desc]):
                        $selClass = ($curStatus === $val) ? "selected-$val" : '';
                        ?>
                        <label class="status-opt <?= $selClass ?>" data-val="<?= $val ?>">
                            <input type="radio" name="status" value="<?= $val ?>" <?= $curStatus === $val ? 'checked' : '' ?> />
                            <p class="status-opt-label"><?= $label ?></p>
                            <p class="status-opt-desc"><?= $desc ?></p>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Submit Bar -->
            <div class="submit-bar">
                <?php if ($isEdit): ?>
                    <a href="listing_detail.php?id=<?= $listingId ?>" class="btn-outline">
                        <i class="ph-bold ph-arrow-left"></i> Batal
                    </a>
                <?php else: ?>
                    <a href="listing.php" class="btn-outline">
                        <i class="ph-bold ph-arrow-left"></i> Batal
                    </a>
                <?php endif; ?>
                <button type="submit" class="btn-primary" id="submitBtn">
                    <i class="ph-bold ph-floppy-disk"></i>
                    <?= $isEdit ? 'Simpan Perubahan' : 'Buat Listing' ?>
                </button>
            </div>
        </form>

    </main>

    <!-- Room Modal -->
    <?php if ($isEdit): ?>
        <div class="room-modal-overlay" id="roomModalOverlay">
            <div class="room-modal-box">
                <div class="room-modal-header">
                    <h3 id="roomModalTitle">Tambah Tipe Kamar</h3>
                    <button class="room-modal-close" id="btnTutupRoomModal">
                        <i class="ph-bold ph-x"></i>
                    </button>
                </div>
                <div class="room-modal-body">
                    <input type="hidden" id="modalRoomId" value="" />

                    <div class="rm-top-row">
                        <input type="file" id="modalRoomFotoInput" accept="image/jpeg,image/png,image/webp"
                            style="display:none;" />
                        <div class="rm-foto-wrap" id="modalRoomFotoArea"
                            onclick="document.getElementById('modalRoomFotoInput').click()">
                            <div class="rm-foto-placeholder" id="modalRoomFotoPlaceholder">
                                <i class="ph-bold ph-camera"></i>
                                <span>Pilih foto kamar</span>
                            </div>
                            <img id="modalRoomFotoImg" src="" alt="" style="display:none;" />
                            <button type="button" id="modalRoomFotoRemove" class="rm-foto-remove"
                                onclick="event.stopPropagation(); clearRoomFoto()">
                                <i class="ph-bold ph-x"></i>
                            </button>
                        </div>

                        <div class="rm-fields-right">
                            <div class="rm-field-row">
                                <div class="rm-field">
                                    <label class="rm-label">Nama kamar <span style="color:#dc2626">*</span></label>
                                    <input type="text" id="modalRoomNama" class="rm-input" placeholder="cth. Suite Deluxe"
                                        maxlength="100" />
                                </div>
                                <div class="rm-field">
                                    <label class="rm-label">Harga per malam <span style="color:#dc2626">*</span></label>
                                    <div class="rm-prefix">
                                        <span class="rm-prefix-text">Rp</span>
                                        <input type="number" id="modalRoomHarga" class="rm-input" placeholder="850000"
                                            min="0" step="1000" />
                                    </div>
                                </div>
                            </div>
                            <div class="rm-field-row">
                                <div class="rm-field">
                                    <label class="rm-label">Ukuran (m²)</label>
                                    <input type="number" id="modalRoomUkuran" class="rm-input" placeholder="cth. 24"
                                        min="1" />
                                </div>
                                <div class="rm-field">
                                    <label class="rm-label">Kapasitas tamu</label>
                                    <div class="rm-stepper">
                                        <button type="button" class="rm-stepper-btn" id="modalTamuMin">−</button>
                                        <span class="rm-stepper-val" id="modalTamuVal">2</span>
                                        <input type="hidden" id="modalRoomMaxTamu" value="2" />
                                        <button type="button" class="rm-stepper-btn" id="modalTamuPlus">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rm-field">
                        <label class="rm-label">Deskripsi singkat</label>
                        <textarea id="modalRoomDeskripsi" class="rm-textarea" rows="2" maxlength="300"
                            placeholder="cth. Kamar nyaman dengan kasur king size dan pemandangan taman..."></textarea>
                    </div>

                    <div>
                        <span class="rm-section-label">Fasilitas kamar</span>
                        <div class="room-facilities-grid-edit" id="modalFasilitasGrid">
                            <?php foreach ($fasilitasKamarMaster as $nama => $icon): ?>
                                <label class="facility-chip-edit">
                                    <input type="checkbox" value="<?= htmlspecialchars($nama) ?>" />
                                    <i class="ph-bold <?= $icon ?>"></i>
                                    <?= htmlspecialchars($nama) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <p class="form-error-inline" id="modalRoomError"></p>
                </div>
                <div class="room-modal-footer">
                    <button type="button" class="btn-outline" id="btnBatalRoomModal">Batal</button>
                    <button type="button" class="btn-primary" id="btnSimpanRoom">
                        <i class="ph-bold ph-floppy-disk"></i> Simpan Kamar
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-column">
                <span class="footer-brand">Teman Singgah</span>
                <p class="footer-description">Platform booking penginapan terpercaya di seluruh Indonesia.</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="footer-copyright">&copy; 2026 Teman Singgah &mdash; All rights reserved.</p>
        </div>
    </footer>

    <div class="ts-toast" id="tsToast"></div>

    <script src="../../../components/navbar.js"></script>
    <script src="../../../popups/auth.js"></script>
    <script>
        const LISTING_ID = <?= (int) $listingId ?>;

        document.querySelectorAll('.stepper-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.target;
                const op = btn.dataset.op;
                const input = document.getElementById(target);
                const display = document.getElementById('val-' + target);
                let val = parseInt(input.value) || 0;
                if (op === 'inc') val++;
                else if (op === 'dec' && val > 1) val--;
                input.value = val;
                display.textContent = val;
            });
        });

        document.querySelectorAll('.facility-check').forEach(label => {
            label.addEventListener('click', () => {
                const cb = label.querySelector('input[type="checkbox"]');
                const icon = label.querySelector('.check-icon');
                cb.checked = !cb.checked;
                label.classList.toggle('selected', cb.checked);
                icon.innerHTML = cb.checked ? '&#10003;' : '';
            });
        });

        document.querySelectorAll('.toggle-opt').forEach(opt => {
            opt.addEventListener('click', () => {
                const group = opt.dataset.group;
                document.querySelectorAll(`.toggle-opt[data-group="${group}"]`).forEach(o => {
                    o.classList.remove('selected-yes', 'selected-no');
                });
                const val = opt.querySelector('input').value;
                opt.classList.add(val === '1' ? 'selected-yes' : 'selected-no');
                opt.querySelector('input').checked = true;
            });
        });

        document.querySelectorAll('.status-opt').forEach(opt => {
            opt.addEventListener('click', () => {
                document.querySelectorAll('.status-opt').forEach(o => o.className = 'status-opt');
                const val = opt.dataset.val;
                opt.classList.add('selected-' + val);
                opt.querySelector('input[type="radio"]').checked = true;
            });
        });

        document.getElementById('photoInput').addEventListener('change', function () {
            const grid = document.getElementById('newPhotoPreview');
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = e => {
                    const wrap = document.createElement('div');
                    wrap.className = 'photo-preview-item';
                    wrap.innerHTML = `
                        <img src="${e.target.result}" alt="Preview" />
                        <button type="button" class="photo-remove-btn"
                                onclick="this.closest('.photo-preview-item').remove()">
                            <i class="ph-bold ph-x"></i>
                        </button>`;
                    grid.appendChild(wrap);
                };
                reader.readAsDataURL(file);
            });
        });

        function removeExistingPhoto(photoId, btn) {
            const item = btn.closest('.photo-preview-item');
            const fd = new FormData();
            fd.append('_aksi', 'hapus_foto');
            fd.append('photo_id', photoId);
            fd.append('id', LISTING_ID);
            fetch('', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'ok') { item.remove(); showToast('Foto dihapus.', 'success'); }
                    else showToast('Gagal hapus foto.', 'error');
                })
                .catch(() => showToast('Gagal terhubung.', 'error'));
        }

        document.getElementById('listingForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="ph-bold ph-spinner" style="animation:spin 1s linear infinite"></i> Menyimpan...';
        });

        function showToast(msg, type = '') {
            const el = document.getElementById('tsToast');
            el.textContent = msg;
            el.className = 'ts-toast' + (type ? ' ' + type : '');
            el.classList.add('show');
            setTimeout(() => el.classList.remove('show'), 3000);
        }

        const style = document.createElement('style');
        style.textContent = '@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }';
        document.head.appendChild(style);

        <?php if ($isEdit): ?>
            let modalTamuCount = 2;
            let modalFotoFile = null;
            let modalFotoName = '';

            const roomOverlay = document.getElementById('roomModalOverlay');
            const roomModalTitle = document.getElementById('roomModalTitle');

            function openRoomModal(room) {
                document.getElementById('modalRoomId').value = room ? (room.id || '') : '';
                roomModalTitle.textContent = room && room.id ? 'Edit Tipe Kamar' : 'Tambah Tipe Kamar';
                document.getElementById('modalRoomNama').value = room ? (room.nama || '') : '';
                document.getElementById('modalRoomHarga').value = room ? (room.harga_malam || '') : '';
                document.getElementById('modalRoomUkuran').value = room ? (room.ukuran_m2 || '') : '';
                document.getElementById('modalRoomDeskripsi').value = room ? (room.deskripsi || '') : '';

                modalTamuCount = room ? (parseInt(room.max_tamu) || 2) : 2;
                document.getElementById('modalTamuVal').textContent = modalTamuCount;
                document.getElementById('modalRoomMaxTamu').value = modalTamuCount;

                document.querySelectorAll('#modalFasilitasGrid .facility-chip-edit').forEach(chip => {
                    const cb = chip.querySelector('input');
                    let fasl = [];
                    if (room && room.fasilitas) {
                        try { fasl = typeof room.fasilitas === 'string' ? JSON.parse(room.fasilitas) : room.fasilitas; } catch (e) { }
                    }
                    cb.checked = Array.isArray(fasl) && fasl.includes(cb.value);
                    chip.classList.toggle('checked', cb.checked);
                });

                clearRoomFoto();
                if (room && room.foto) {
                    modalFotoName = room.foto;
                    const img = document.getElementById('modalRoomFotoImg');
                    img.src = '/teman_singgah/assets/uploads/rooms/' + room.foto + '?v=' + Date.now();
                    img.style.display = 'block';
                    document.getElementById('modalRoomFotoPlaceholder').style.display = 'none';
                    document.getElementById('modalRoomFotoRemove').style.display = 'flex';
                }

                document.getElementById('modalRoomError').style.display = 'none';
                document.getElementById('modalRoomError').textContent = '';
                roomOverlay.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            function closeRoomModal() {
                roomOverlay.classList.remove('open');
                document.body.style.overflow = '';
            }

            function clearRoomFoto() {
                modalFotoFile = null;
                modalFotoName = '';
                const img = document.getElementById('modalRoomFotoImg');
                img.src = '';
                img.style.display = 'none';
                document.getElementById('modalRoomFotoPlaceholder').style.display = 'flex';
                document.getElementById('modalRoomFotoRemove').style.display = 'none';
                document.getElementById('modalRoomFotoInput').value = '';
            }

            document.getElementById('btnTambahKamar').addEventListener('click', () => openRoomModal(null));
            document.getElementById('btnTutupRoomModal').addEventListener('click', closeRoomModal);
            document.getElementById('btnBatalRoomModal').addEventListener('click', closeRoomModal);
            roomOverlay.addEventListener('click', e => { if (e.target === roomOverlay) closeRoomModal(); });

            document.getElementById('modalTamuMin').addEventListener('click', () => {
                if (modalTamuCount > 1) {
                    modalTamuCount--;
                    document.getElementById('modalTamuVal').textContent = modalTamuCount;
                    document.getElementById('modalRoomMaxTamu').value = modalTamuCount;
                }
            });

            document.getElementById('modalTamuPlus').addEventListener('click', () => {
                if (modalTamuCount < 20) {
                    modalTamuCount++;
                    document.getElementById('modalTamuVal').textContent = modalTamuCount;
                    document.getElementById('modalRoomMaxTamu').value = modalTamuCount;
                }
            });

            document.getElementById('modalRoomFotoInput').addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;
                if (file.size > 2 * 1024 * 1024) {
                    showToast('Ukuran foto maksimal 2MB.', 'error');
                    this.value = '';
                    return;
                }
                modalFotoFile = file;
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.getElementById('modalRoomFotoImg');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    document.getElementById('modalRoomFotoPlaceholder').style.display = 'none';
                    document.getElementById('modalRoomFotoRemove').style.display = 'flex';
                };
                reader.readAsDataURL(file);
            });

            document.querySelectorAll('#modalFasilitasGrid .facility-chip-edit').forEach(chip => {
                chip.addEventListener('click', () => {
                    const cb = chip.querySelector('input');
                    cb.checked = !cb.checked;
                    chip.classList.toggle('checked', cb.checked);
                });
            });

            document.getElementById('btnSimpanRoom').addEventListener('click', () => {
                const nama = document.getElementById('modalRoomNama').value.trim();
                const harga = parseFloat(document.getElementById('modalRoomHarga').value);
                const errEl = document.getElementById('modalRoomError');

                if (!nama) {
                    errEl.textContent = 'Nama kamar wajib diisi.';
                    errEl.style.display = 'block';
                    return;
                }
                if (!harga || harga <= 0) {
                    errEl.textContent = 'Harga per malam wajib diisi.';
                    errEl.style.display = 'block';
                    return;
                }
                errEl.style.display = 'none';

                const roomId = document.getElementById('modalRoomId').value;
                const fasilitas = [...document.querySelectorAll('#modalFasilitasGrid .facility-chip-edit input:checked')]
                    .map(cb => cb.value);

                const fd = new FormData();
                fd.append('_aksi', 'simpan_room');
                fd.append('id', LISTING_ID);
                fd.append('room_id', roomId || 0);
                fd.append('nama', nama);
                fd.append('deskripsi', document.getElementById('modalRoomDeskripsi').value.trim());
                fd.append('ukuran_m2', document.getElementById('modalRoomUkuran').value || '');
                fd.append('max_tamu', modalTamuCount);
                fd.append('harga_malam', harga);
                fasilitas.forEach(f => fd.append('fasilitas[]', f));
                if (modalFotoFile) fd.append('foto', modalFotoFile);

                const btn = document.getElementById('btnSimpanRoom');
                btn.disabled = true;
                btn.innerHTML = '<i class="ph-bold ph-spinner" style="animation:spin 1s linear infinite"></i> Menyimpan...';

                fetch('', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(d => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ph-bold ph-floppy-disk"></i> Simpan Kamar';
                        if (d.status === 'ok') {
                            closeRoomModal();
                            showToast('Kamar disimpan.', 'success');
                            setTimeout(() => window.location.reload(), 900);
                        } else {
                            errEl.textContent = d.message || 'Terjadi kesalahan.';
                            errEl.style.display = 'block';
                        }
                    })
                    .catch(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ph-bold ph-floppy-disk"></i> Simpan Kamar';
                        showToast('Gagal terhubung ke server.', 'error');
                    });
            });

            function deleteRoom(roomId) {
                if (!confirm('Hapus kamar ini? Tindakan tidak dapat dibatalkan.')) return;
                const fd = new FormData();
                fd.append('_aksi', 'hapus_room');
                fd.append('id', LISTING_ID);
                fd.append('room_id', roomId);
                fetch('', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(d => {
                        if (d.status === 'ok') {
                            const el = document.querySelector(`.room-entry-edit[data-room-id="${roomId}"]`);
                            if (el) el.remove();
                            showToast('Kamar dihapus.', 'success');
                        } else {
                            showToast('Gagal hapus kamar.', 'error');
                        }
                    })
                    .catch(() => showToast('Gagal terhubung ke server.', 'error'));
            }
        <?php endif; ?>
    </script>
</body>

</html>