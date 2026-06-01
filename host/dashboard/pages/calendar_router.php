<?php
// ─── CORS & Content-Type ───────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit(); }

// ─── Session & Auth ────────────────────────────────────────────────────────
session_start();
$session_host_id = $_SESSION['host_id'] ?? 1; // fallback ke 1 untuk testing

// ─── Database Connection ───────────────────────────────────────────────────
$host = "localhost"; $user = "root"; $pass = ""; $db = "teman_singgah";
$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    echo json_encode(["status" => "error", "message" => "Gagal terhubung ke database."]);
    exit();
}
mysqli_set_charset($koneksi, "utf8mb4");

// ─── Helper: Hitung overlap booking aktif untuk 1 tanggal ─────────────────
function getOverlapCount($koneksi, $listing_id, $date) {
    $stmt = mysqli_prepare($koneksi,
        "SELECT COUNT(*) AS cnt
         FROM bookings
         WHERE listing_id = ?
           AND status IN ('dikonfirmasi','menunggu')
           AND checkin  <= ?
           AND checkout >  ?"
    );
    mysqli_stmt_bind_param($stmt, "iss", $listing_id, $date, $date);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return (int)($row['cnt'] ?? 0);
}

// ─── Helper: Ambil custom price untuk 1 tanggal ────────────────────────────
function getCustomPrice($koneksi, $listing_id, $date, $room_id = null) {
    if ($room_id !== null) {
        $stmt = mysqli_prepare($koneksi,
            "SELECT custom_price FROM date_prices
             WHERE listing_id = ? AND price_date = ? AND room_id = ?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "isi", $listing_id, $date, $room_id);
    } else {
        $stmt = mysqli_prepare($koneksi,
            "SELECT custom_price FROM date_prices
             WHERE listing_id = ? AND price_date = ? AND room_id IS NULL
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "is", $listing_id, $date);
    }
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ? (float)$row['custom_price'] : null;
}

// ─── Helper: Ambil harga default listing (weekday vs weekend) ─────────────
function getBasePrice($koneksi, $listing_id, $date) {
    $stmt = mysqli_prepare($koneksi,
        "SELECT harga_malam, harga_akhir_pekan FROM listings WHERE id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $listing_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row) return 0;

    $dow = (int)date('N', strtotime($date)); // 1=Senin … 7=Minggu
    $is_weekend = ($dow >= 6); // Sabtu=6, Minggu=7
    return $is_weekend && $row['harga_akhir_pekan'] > 0
        ? (float)$row['harga_akhir_pekan']
        : (float)$row['harga_malam'];
}

// ─── Helper: Total stok kamar untuk 1 listing ─────────────────────────────
function getTotalStok($koneksi, $listing_id) {
    $stmt = mysqli_prepare($koneksi,
        "SELECT COALESCE(SUM(stok), 0) AS total FROM listing_rooms WHERE listing_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $listing_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return (int)($row['total'] ?? 0);
}

// ─── Helper: Cek apakah tanggal ter-blocked ───────────────────────────────
function isBlocked($koneksi, $listing_id, $date) {
    $stmt = mysqli_prepare($koneksi,
        "SELECT id, reason FROM blocked_dates
         WHERE listing_id = ? AND blocked_date = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "is", $listing_id, $date);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

// ─── Routing ───────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── GET LISTINGS ──────────────────────────────────────────────────────
    case 'get_listings': {
        $stmt = mysqli_prepare($koneksi,
            "SELECT id, judul, foto_utama FROM listings WHERE host_id = ? ORDER BY id ASC"
        );
        mysqli_stmt_bind_param($stmt, "i", $session_host_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $listings = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $listings[] = [
                'id'         => (int)$row['id'],
                'judul'      => $row['judul'],
                'foto_utama' => $row['foto_utama'],
            ];
        }
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'success', 'listings' => $listings]);
        break;
    }

    // ── GET CALENDAR ──────────────────────────────────────────────────────
    case 'get_calendar': {
        $listing_id = (int)($_GET['listing_id'] ?? 0);
        $month      = (int)($_GET['month']      ?? date('n'));
        $year       = (int)($_GET['year']       ?? date('Y'));

        if (!$listing_id || $month < 1 || $month > 12) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
            break;
        }

        $total_stok  = getTotalStok($koneksi, $listing_id);
        $days_in_month = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
        $calendar = [];

        for ($d = 1; $d <= $days_in_month; $d++) {
            $date_str = sprintf('%04d-%02d-%02d', $year, $month, $d);

            // 1. Cek blocked
            $blocked = isBlocked($koneksi, $listing_id, $date_str);
            if ($blocked) {
                $calendar[$date_str] = [
                    'status'       => 'blocked',
                    'price'        => null,
                    'stok_total'   => $total_stok,
                    'stok_tersisa' => 0,
                ];
                continue;
            }

            // 2 & 3. Hitung booked vs stok
            $booked = getOverlapCount($koneksi, $listing_id, $date_str);
            $sisa   = max(0, $total_stok - $booked);

            if ($total_stok > 0 && $booked >= $total_stok) {
                $day_status = 'booked';
            } elseif ($total_stok > 0 && $booked > 0) {
                $day_status = 'partial'; // sebagian kamar dipesan
            } else {
                $day_status = 'available';
            }

            // Harga
            $custom = getCustomPrice($koneksi, $listing_id, $date_str);
            $price  = $custom !== null ? $custom : getBasePrice($koneksi, $listing_id, $date_str);

            $calendar[$date_str] = [
                'status'       => $day_status,
                'price'        => $price,
                'stok_total'   => $total_stok,
                'stok_tersisa' => $sisa,
            ];
        }

        echo json_encode([
            'status'     => 'success',
            'listing_id' => $listing_id,
            'month'      => $month,
            'year'       => $year,
            'calendar'   => $calendar,
        ]);
        break;
    }

    // ── GET DAY DETAIL ────────────────────────────────────────────────────
    case 'get_day_detail': {
        $listing_id = (int)($_GET['listing_id'] ?? 0);
        $date       = $_GET['date'] ?? '';

        if (!$listing_id || !$date || !strtotime($date)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
            break;
        }

        // Status & harga
        $blocked     = isBlocked($koneksi, $listing_id, $date);
        $total_stok  = getTotalStok($koneksi, $listing_id);
        $booked      = getOverlapCount($koneksi, $listing_id, $date);
        $sisa        = max(0, $total_stok - $booked);

        if ($blocked) {
            $day_status = 'blocked';
        } elseif ($total_stok > 0 && $booked >= $total_stok) {
            $day_status = 'booked';
        } elseif ($total_stok > 0 && $booked > 0) {
            $day_status = 'partial';
        } else {
            $day_status = 'available';
        }

        $custom = getCustomPrice($koneksi, $listing_id, $date);
        $harga  = $custom !== null ? $custom : getBasePrice($koneksi, $listing_id, $date);

        // Daftar booking overlap + nama tamu
        $stmt = mysqli_prepare($koneksi,
            "SELECT b.id, u.nama AS nama_tamu, b.checkin, b.checkout,
                    b.status, b.jumlah_tamu, b.room_id
             FROM bookings b
             LEFT JOIN users u ON u.id = b.user_id
             WHERE b.listing_id = ?
               AND b.status IN ('dikonfirmasi','menunggu','selesai')
               AND b.checkin  <= ?
               AND b.checkout >  ?
             ORDER BY b.checkin ASC"
        );
        mysqli_stmt_bind_param($stmt, "iss", $listing_id, $date, $date);
        mysqli_stmt_execute($stmt);
        $res      = mysqli_stmt_get_result($stmt);
        $bookings = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $bookings[] = [
                'id'          => (int)$row['id'],
                'nama_tamu'   => $row['nama_tamu'] ?? '(Tamu)',
                'checkin'     => $row['checkin'],
                'checkout'    => $row['checkout'],
                'status'      => $row['status'],
                'jumlah_tamu' => (int)$row['jumlah_tamu'],
                'room_id'     => $row['room_id'] ? (int)$row['room_id'] : null,
            ];
        }
        mysqli_stmt_close($stmt);

        echo json_encode([
            'status'       => 'success',
            'date'         => $date,
            'day_status'   => $day_status,
            'harga'        => $harga,
            'stok_total'   => $total_stok,
            'stok_tersisa' => $sisa,
            'bookings'     => $bookings,
            'blocked_info' => $blocked ? ['reason' => $blocked['reason']] : null,
        ]);
        break;
    }

    // ── BLOCK DATE ────────────────────────────────────────────────────────
    case 'block_date': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method harus POST.']);
            break;
        }
        $body       = json_decode(file_get_contents('php://input'), true) ?? [];
        $listing_id = (int)($body['listing_id'] ?? $_POST['listing_id'] ?? 0);
        $date       = $body['date']       ?? $_POST['date']       ?? '';
        $reason     = $body['reason']     ?? $_POST['reason']     ?? 'manual';
        $room_id    = isset($body['room_id']) ? (int)$body['room_id'] : (isset($_POST['room_id']) ? (int)$_POST['room_id'] : null);

        if (!$listing_id || !$date || !strtotime($date)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
            break;
        }
        if (!in_array($reason, ['manual', 'maintenance'])) {
            echo json_encode(['status' => 'error', 'message' => 'Reason tidak valid.']);
            break;
        }

        // Cegah block jika ada booking dikonfirmasi
        $stmt = mysqli_prepare($koneksi,
            "SELECT COUNT(*) AS cnt FROM bookings
             WHERE listing_id = ?
               AND status = 'dikonfirmasi'
               AND checkin  <= ?
               AND checkout >  ?"
        );
        mysqli_stmt_bind_param($stmt, "iss", $listing_id, $date, $date);
        mysqli_stmt_execute($stmt);
        $check = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ((int)$check['cnt'] > 0) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Tidak bisa memblokir tanggal ini karena sudah ada booking yang dikonfirmasi.',
            ]);
            break;
        }

        // Insert
        if ($room_id !== null) {
            $stmt = mysqli_prepare($koneksi,
                "INSERT IGNORE INTO blocked_dates (listing_id, room_id, blocked_date, reason)
                 VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "iiss", $listing_id, $room_id, $date, $reason);
        } else {
            $stmt = mysqli_prepare($koneksi,
                "INSERT IGNORE INTO blocked_dates (listing_id, room_id, blocked_date, reason)
                 VALUES (?, NULL, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "iss", $listing_id, $date, $reason);
        }
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode([
            'status'  => $affected > 0 ? 'success' : 'info',
            'message' => $affected > 0 ? 'Tanggal berhasil diblokir.' : 'Tanggal sudah terblokir sebelumnya.',
        ]);
        break;
    }

    // ── UNBLOCK DATE ──────────────────────────────────────────────────────
    case 'unblock_date': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method harus POST.']);
            break;
        }
        $body       = json_decode(file_get_contents('php://input'), true) ?? [];
        $listing_id = (int)($body['listing_id'] ?? $_POST['listing_id'] ?? 0);
        $date       = $body['date']    ?? $_POST['date']    ?? '';
        $room_id    = isset($body['room_id']) ? (int)$body['room_id'] : (isset($_POST['room_id']) ? (int)$_POST['room_id'] : null);

        if (!$listing_id || !$date) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
            break;
        }

        if ($room_id !== null) {
            $stmt = mysqli_prepare($koneksi,
                "DELETE FROM blocked_dates
                 WHERE listing_id = ? AND blocked_date = ? AND room_id = ?"
            );
            mysqli_stmt_bind_param($stmt, "isi", $listing_id, $date, $room_id);
        } else {
            $stmt = mysqli_prepare($koneksi,
                "DELETE FROM blocked_dates
                 WHERE listing_id = ? AND blocked_date = ? AND room_id IS NULL"
            );
            mysqli_stmt_bind_param($stmt, "is", $listing_id, $date);
        }
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode([
            'status'  => 'success',
            'message' => $affected > 0 ? 'Blokir tanggal berhasil dihapus.' : 'Tanggal tidak ditemukan dalam daftar blokir.',
        ]);
        break;
    }

    // ── UPDATE PRICE ──────────────────────────────────────────────────────
    case 'update_price': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method harus POST.']);
            break;
        }
        $body         = json_decode(file_get_contents('php://input'), true) ?? [];
        $listing_id   = (int)($body['listing_id']   ?? $_POST['listing_id']   ?? 0);
        $date         = $body['date']         ?? $_POST['date']         ?? '';
        $custom_price = (float)($body['custom_price'] ?? $_POST['custom_price'] ?? 0);
        $room_id      = isset($body['room_id']) ? (int)$body['room_id'] : (isset($_POST['room_id']) ? (int)$_POST['room_id'] : null);

        if (!$listing_id || !$date || $custom_price <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
            break;
        }

        if ($room_id !== null) {
            $stmt = mysqli_prepare($koneksi,
                "INSERT INTO date_prices (listing_id, room_id, price_date, custom_price)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE custom_price = VALUES(custom_price)"
            );
            mysqli_stmt_bind_param($stmt, "iisd", $listing_id, $room_id, $date, $custom_price);
        } else {
            $stmt = mysqli_prepare($koneksi,
                "INSERT INTO date_prices (listing_id, room_id, price_date, custom_price)
                 VALUES (?, NULL, ?, ?)
                 ON DUPLICATE KEY UPDATE custom_price = VALUES(custom_price)"
            );
            mysqli_stmt_bind_param($stmt, "isd", $listing_id, $date, $custom_price);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode(['status' => 'success', 'message' => 'Harga berhasil diperbarui.']);
        break;
    }

    // ── UPDATE SETTINGS ───────────────────────────────────────────────────
    case 'update_settings': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method harus POST.']);
            break;
        }
        $body       = json_decode(file_get_contents('php://input'), true) ?? [];
        $listing_id = (int)($body['listing_id'] ?? $_POST['listing_id'] ?? 0);
        $field      = $body['field'] ?? $_POST['field'] ?? '';
        $value      = $body['value'] ?? $_POST['value'] ?? null;

        $allowed_fields = [
            'harga_malam', 'harga_akhir_pekan', 'diskon_mingguan',
            'diskon_bulanan', 'min_malam', 'max_malam',
            'jam_checkin', 'jam_checkout',
        ];

        if (!$listing_id || !in_array($field, $allowed_fields) || $value === null) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
            break;
        }

        // Validasi kepemilikan listing
        $stmt = mysqli_prepare($koneksi,
            "SELECT id FROM listings WHERE id = ? AND host_id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "ii", $listing_id, $session_host_id);
        mysqli_stmt_execute($stmt);
        $own = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$own) {
            echo json_encode(['status' => 'error', 'message' => 'Listing tidak ditemukan atau bukan milik Anda.']);
            break;
        }

        // Gunakan field yang sudah divalidasi dari whitelist (aman dari SQL injection)
        $sql  = "UPDATE listings SET `{$field}` = ? WHERE id = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        // Deteksi tipe: jam pakai string, sisanya numerik
        if (in_array($field, ['jam_checkin', 'jam_checkout'])) {
            mysqli_stmt_bind_param($stmt, "si", $value, $listing_id);
        } else {
            $num_value = (float)$value;
            mysqli_stmt_bind_param($stmt, "di", $num_value, $listing_id);
            $value = $num_value;
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode([
            'status'    => 'success',
            'message'   => 'Pengaturan berhasil diperbarui.',
            'new_value' => $value,
        ]);
        break;
    }

    default:
        echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenal.']);
        break;
}

mysqli_close($koneksi);