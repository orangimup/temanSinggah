<?php
// navbar_profile.php — include di bagian nav-right semua halaman
// Letakkan di /teman_singgah/components/navbar_profile.php
// WAJIB: pastikan auth_session.php sudah di-include sebelumnya
?>
<div class="icon-buttons">
  <?php if ($isLoggedIn): ?>
    <button
      class="icon-button profile"
      aria-label="Profile"
      <?= $userPhoto ? 'style="padding:0;overflow:hidden;"' : '' ?>
    >
      <?php if ($userPhoto): ?>
        <img
          src="<?= $userPhoto ?>"
          alt="Foto Profil"
          style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
      <?php else: ?>
        <?= htmlspecialchars($userInitial) ?>
      <?php endif; ?>
    </button>
  <?php else: ?>
    <button class="icon-button profile hidden" aria-label="Profile"></button>
    <button class="icon-button hamburger" aria-label="Hamburger">
      <i class="ph-bold ph-list"></i>
    </button>
  <?php endif; ?>
</div>
<div id="hamburgerDropdown"></div>
<div id="languagePopup"></div>