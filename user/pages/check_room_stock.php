<?php
function syncRoomStock(mysqli $koneksi, int $listing_id, int $room_id): void
{
    // Ambil stok total kamar
    $r = mysqli_query(
        $koneksi,
        "SELECT stok FROM listing_rooms 
         WHERE id = $room_id AND listing_id = $listing_id LIMIT 1"
    );
    $row = mysqli_fetch_assoc($r);
    if (!$row)
        return;
    $stok_total = (int) $row['stok'];

    // Hitung booking aktif per tanggal untuk kamar ini
    $r = mysqli_query(
        $koneksi,
        "SELECT checkin, checkout FROM bookings
         WHERE listing_id = $listing_id 
         AND room_id = $room_id
         AND status IN ('menunggu','dikonfirmasi')"
    );

    $counter = [];
    while ($b = mysqli_fetch_assoc($r)) {
        $period = new DatePeriod(
            new DateTime($b['checkin']),
            new DateInterval('P1D'),
            new DateTime($b['checkout'])
        );
        foreach ($period as $d) {
            $key = $d->format('Y-m-d');
            $counter[$key] = ($counter[$key] ?? 0) + 1;
        }
    }

    $now = date('Y-m-d');

    // Block tanggal yang stoknya penuh, buka yang sudah ada ruang lagi
    foreach ($counter as $date => $booked) {
        if ($date < $now)
            continue;

        $esc = mysqli_real_escape_string($koneksi, $date);
        $chk = mysqli_query(
            $koneksi,
            "SELECT id FROM blocked_dates
             WHERE listing_id = $listing_id AND room_id = $room_id
             AND blocked_date = '$esc' AND blocked_by = 'system' AND unlocked_at IS NULL"
        );
        $exists = mysqli_num_rows($chk) > 0;

        if ($booked >= $stok_total && !$exists) {
            mysqli_query(
                $koneksi,
                "INSERT IGNORE INTO blocked_dates
                 (listing_id, room_id, blocked_date, blocked_by, reason)
                 VALUES ($listing_id, $room_id, '$esc', 'system', 'Stok kamar penuh')"
            );
        } elseif ($booked < $stok_total && $exists) {
            mysqli_query(
                $koneksi,
                "UPDATE blocked_dates SET unlocked_at = NOW()
                 WHERE listing_id = $listing_id AND room_id = $room_id
                 AND blocked_date = '$esc' AND blocked_by = 'system' AND unlocked_at IS NULL"
            );
        }
    }

    // Buka tanggal yang tidak ada booking sama sekali
    if (empty($counter)) {
        mysqli_query(
            $koneksi,
            "UPDATE blocked_dates SET unlocked_at = NOW()
             WHERE listing_id = $listing_id AND room_id = $room_id
             AND blocked_by = 'system' AND unlocked_at IS NULL"
        );
    } else {
        $tanggal_ada = "'" . implode("','", array_keys($counter)) . "'";
        mysqli_query(
            $koneksi,
            "UPDATE blocked_dates SET unlocked_at = NOW()
             WHERE listing_id = $listing_id AND room_id = $room_id
             AND blocked_by = 'system' AND unlocked_at IS NULL
             AND blocked_date NOT IN ($tanggal_ada)"
        );
    }
}