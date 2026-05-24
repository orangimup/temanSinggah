<?php
session_start();
require_once '../../../koneksi.php';

header('Content-Type: application/json');

$required = [
    'tipe_properti', 'tipe_privasi', 'tipe_booking',
    'lokasi', 'latitude', 'longitude',
    'max_tamu', 'kamar_tidur', 'tempat_tidur', 'kamar_mandi',
    'foto', 'judul', 'deskripsi', 'harga_malam'
];

foreach ($required as $field) {
    if (!isset($_SESSION['onboarding'][$field]) || $_SESSION['onboarding'][$field] === '') {
        echo json_encode(['status' => 'error', 'message' => "Data $field belum diisi"]);
        exit();
    }
}

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi login tidak ditemukan']);
    exit();
}

$o       = $_SESSION['onboarding'];
$host_id = $_SESSION['id'];

mysqli_begin_transaction($koneksi);

try {
    // 1. simpan listings
    $stmt = mysqli_prepare($koneksi, "
        INSERT INTO listings
            (host_id, judul, deskripsi, tipe_properti, tipe_privasi, tipe_booking,
             lokasi, latitude, longitude, harga_malam,
             max_tamu, kamar_tidur, tempat_tidur, kamar_mandi, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif')
    ");
    mysqli_stmt_bind_param($stmt, 'issssssddiiiii',
        $host_id,
        $o['judul'],
        $o['deskripsi'],
        $o['tipe_properti'],
        $o['tipe_privasi'],
        $o['tipe_booking'],
        $o['lokasi'],
        $o['latitude'],
        $o['longitude'],
        $o['harga_malam'],
        $o['max_tamu'],
        $o['kamar_tidur'],
        $o['tempat_tidur'],
        $o['kamar_mandi']
    );
    mysqli_stmt_execute($stmt);
    $listing_id = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);

    // 2. simpan foto — 1 query sekaligus
    $foto = $o['foto'];
    $foto_values = [];
    $foto_params = [];
    foreach ($foto as $index => $namaFile) {
        $foto_values[] = "(?, ?, ?, ?)";
        $foto_params[] = $listing_id;
        $foto_params[] = $namaFile;
        $foto_params[] = ($index === 0) ? 1 : 0;
        $foto_params[] = $index;
    }
    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO listing_photos (listing_id, nama_file, adalah_cover, urutan) VALUES "
        . implode(', ', $foto_values)
    );
    mysqli_stmt_bind_param($stmt, str_repeat('isii', count($foto)), ...$foto_params);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 3. simpan fasilitas — 1 query sekaligus
    if (!empty($o['fasilitas'])) {
        $fasilitas_label = [
            'wifi'         => 'Wi-Fi',
            'tv'           => 'TV',
            'ac'           => 'AC / Pendingin Ruangan',
            'dapur'        => 'Dapur',
            'mesin_cuci'   => 'Mesin Cuci',
            'parkir'       => 'Parkir Gratis',
            'kolam_renang' => 'Kolam Renang',
            'p3k'          => 'Kotak P3K',
            'pemadam'      => 'Alat Pemadam',
            'air_panas'    => 'Shower Air Panas',
            'ruang_kerja'  => 'Ruang Kerja',
            'hewan'        => 'Ramah Hewan Peliharaan',
        ];

        $fas_values = [];
        $fas_params = [];
        foreach ($o['fasilitas'] as $key) {
            if (!isset($fasilitas_label[$key])) continue;
            $fas_values[] = "(?, ?)";
            $fas_params[] = $listing_id;
            $fas_params[] = $fasilitas_label[$key];
        }

        if (!empty($fas_values)) {
            $stmt = mysqli_prepare($koneksi,
                "INSERT INTO listing_amenities (listing_id, nama_fasilitas) VALUES "
                . implode(', ', $fas_values)
            );
            mysqli_stmt_bind_param($stmt, str_repeat('is', count($fas_values)), ...$fas_params);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // 4. simpan diskon — 1 query sekaligus
    if (!empty($o['diskon'])) {
        $diskon_config = [
            'tamu_baru' => 20,
            'mingguan'  => 10,
            'bulanan'   => 15,
        ];

        $dis_values = [];
        $dis_params = [];
        foreach ($o['diskon'] as $tipe) {
            if (!isset($diskon_config[$tipe])) continue;
            $dis_values[] = "(?, ?, ?)";
            $dis_params[] = $listing_id;
            $dis_params[] = $tipe;
            $dis_params[] = $diskon_config[$tipe];
        }

        if (!empty($dis_values)) {
            $stmt = mysqli_prepare($koneksi,
                "INSERT INTO listing_discounts (listing_id, tipe, persentase) VALUES "
                . implode(', ', $dis_values)
            );
            mysqli_stmt_bind_param($stmt, str_repeat('isi', count($dis_values)), ...$dis_params);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    mysqli_commit($koneksi);
    unset($_SESSION['onboarding']);

    echo json_encode(['status' => 'ok', 'listing_id' => $listing_id]);

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan, coba lagi']);
}