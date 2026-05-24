<?php
$hostNama = $_SESSION['nama'] ?? '';
$hostFotoRaw = $_SESSION['photo'] ?? '';
$hostInisial = strtoupper(mb_substr($hostNama, 0, 1));

$fotoPath = '';
if (!empty($hostFotoRaw)) {
    $abs = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $hostFotoRaw;
    if (file_exists($abs)) {
        $fotoPath = '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($hostFotoRaw);
    }
}
?>
<div class="nav-right">
    <a href="/teman_singgah/index.php">
        <button class="ghost-button">Ganti ke Pengunjung</button>
    </a>
    <div class="icon-buttons" style="position:relative;">
        <button class="icon-button profile host-profile-trigger" aria-label="Profil Host" <?= $fotoPath ? 'style="padding:0;overflow:hidden;"' : '' ?>>
            <?php if ($fotoPath): ?>
                <img src="<?= $fotoPath ?>" alt="Foto Profil"
                    style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
            <?php else: ?>
                <?= htmlspecialchars($hostInisial) ?>
            <?php endif; ?>
        </button>

        <!-- Dropdown -->
        <div class="host-profile-dropdown" id="hostProfileDropdown">
            <div class="hpd-header">
                <div class="hpd-avatar">
                    <?php if ($fotoPath): ?>
                        <img src="<?= $fotoPath ?>" alt="Foto" />
                    <?php else: ?>
                        <?= htmlspecialchars($hostInisial) ?>
                    <?php endif; ?>
                </div>
                <div class="hpd-info">
                    <span class="hpd-name"><?= htmlspecialchars($hostNama) ?></span>
                    <span class="hpd-role">Host</span>
                </div>
            </div>
            <div class="hpd-divider"></div>
            <a href="/teman_singgah/host/dashboard/pages/profile_host.php" class="hpd-item">
                <i class="ph-bold ph-user-circle"></i> Profil Host
            </a>
            <a href="/teman_singgah/host/dashboard/pages/listing.php" class="hpd-item">
                <i class="ph-bold ph-house-line"></i> Listing Saya
            </a>
            <a href="/teman_singgah/host/dashboard/pages/reservations.php" class="hpd-item">
                <i class="ph-bold ph-calendar-check"></i> Reservasi
            </a>
            <div class="hpd-divider"></div>
            <a href="/teman_singgah/index.php" class="hpd-item">
                <i class="ph-bold ph-arrow-left"></i> Ganti ke Pengunjung
            </a>
            <a href="/teman_singgah/auth/logout.php" class="hpd-item hpd-logout">
                <i class="ph-bold ph-sign-out"></i> Keluar
            </a>
        </div>
    </div>
</div>