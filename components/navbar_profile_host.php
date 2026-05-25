<?php
// Variabel $userName, $userInitial, $userPhoto sudah diset oleh guard_host.php
?>
<div class="nav-right">
    <a href="/teman_singgah/index.php">
        <button class="ghost-button">Ganti ke Pengunjung</button>
    </a>
    <div class="icon-buttons" style="position:relative;">
        <button class="icon-button profile host-profile-trigger"
                aria-label="Profil Host"
                <?= $userPhoto ? 'style="padding:0;overflow:hidden;"' : '' ?>>
            <?php if ($userPhoto): ?>
                <img src="<?= htmlspecialchars($userPhoto) ?>" alt="Foto Profil"
                     style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
            <?php else: ?>
                <?= htmlspecialchars($userInitial) ?>
            <?php endif; ?>
        </button>

        <div class="host-profile-dropdown" id="hostProfileDropdown">
            <div class="hpd-header">
                <div class="hpd-avatar">
                    <?php if ($userPhoto): ?>
                        <img src="<?= htmlspecialchars($userPhoto) ?>" alt="Foto" />
                    <?php else: ?>
                        <?= htmlspecialchars($userInitial) ?>
                    <?php endif; ?>
                </div>
                <div class="hpd-info">
                    <span class="hpd-name"><?= htmlspecialchars($userName) ?></span>
                    <span class="hpd-role">Host</span>
                </div>
            </div>
            <div class="hpd-divider"></div>
            <a href="/teman_singgah/host/account_host.php" class="hpd-item">
                <i class="ph-bold ph-user-circle"></i> Profil Saya
            </a>
            <a href="/teman_singgah/host/dashboard/pages/listing.php" class="hpd-item">
                <i class="ph-bold ph-house-line"></i> Listing Saya
            </a>
            <a href="/teman_singgah/host/dashboard/pages/reservations.php" class="hpd-item">
                <i class="ph-bold ph-calendar-check"></i> Reservasi
            </a>
            <div class="hpd-divider"></div>
            <a href="/teman_singgah/auth/proses_logout.php" class="hpd-item hpd-logout">
                <i class="ph-bold ph-sign-out"></i> Keluar
            </a>
        </div>
    </div>
</div>