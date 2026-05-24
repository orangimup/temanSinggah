<?php
?>

<div class="nav-right">

  <!-- Tombol switch ke mode pengunjung — hanya tampil saat login -->
  <?php if ($isLoggedIn): ?>
    <a href="/teman_singgah/index.php">
      <button class="ghost-button">Ganti ke pengunjung</button>
    </a>
  <?php endif; ?>

  <div class="icon-buttons">

    <!-- Tombol Profile -->
    <?php if ($isLoggedIn): ?>
      <button
        class="icon-button profile"
        aria-label="Profil <?= htmlspecialchars($userName) ?>"
        <?= !empty($userPhoto) ? 'style="padding:0;overflow:hidden;"' : '' ?>
      >
        <?php if (!empty($userPhoto)): ?>
          <img
            src="<?= htmlspecialchars($userPhoto) ?>"
            alt="Foto profil"
            style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
        <?php else: ?>
          <?= htmlspecialchars($userInitial) ?>
        <?php endif; ?>
      </button>
    <?php else: ?>
      <!-- Sembunyikan profile jika belum login, biar layout tidak geser -->
      <button class="icon-button profile hidden" aria-label="Profile" aria-hidden="true"></button>
    <?php endif; ?>

    <!-- Hamburger — SELALU ada untuk mobile nav -->
    <button class="icon-button hamburger" aria-label="Menu navigasi">
      <i class="ph-bold ph-list"></i>
    </button>

  </div><!-- /.icon-buttons -->

  <div id="hamburgerDropdown"></div>
  <div id="languagePopup"></div>
  <div id="authPopup"></div>

</div><!-- /.nav-right -->