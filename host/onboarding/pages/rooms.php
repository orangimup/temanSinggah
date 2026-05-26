<?php
session_start();

// Ambil data kamar yang sudah disimpan di session (jika ada)
$saved_rooms = isset($_SESSION['onboarding']['rooms']) ? $_SESSION['onboarding']['rooms'] : [];
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pilihan Kamar | Teman Singgah</title>
    <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />

    <link rel="stylesheet" href="../../../components/root.css" />
    <link rel="stylesheet" href="../../../components/navbar.css" />
    <link rel="stylesheet" href="../onboarding.css" />
    <link rel="stylesheet" href="../rooms.css" />

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  </head>

  <body class="onboarding-page">
    <header class="navbar">
      <nav class="navbar-container">
        <a href="../../../index.php" class="logo-link"></a>
        <div class="logo-section">
          <img src="../../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
          <img src="../../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
        </div>
        <div class="header-actions">
          <a href="../../../user/pages/messages.html"><button class="ghost-button">Pertanyaan?</button></a>
          <a href="../../../index.php"><button class="ghost-button">Simpan & keluar</button></a>
        </div>
      </nav>
    </header>

    <main class="main-content wide">
      <div class="page-header">
        <h2>Pilihan kamar yang tersedia</h2>
        <p>Tambahkan tipe-tipe kamar yang bisa dipesan tamu. Minimal 1 kamar diperlukan.</p>
      </div>

      <!-- List kamar yang sudah ditambah -->
      <div id="roomsList" class="rooms-list">
        <?php if (!empty($saved_rooms)): ?>
          <?php foreach ($saved_rooms as $i => $room): ?>
            <div class="room-entry" data-index="<?= $i ?>">
              <div class="room-entry-header">
                <div class="room-entry-info">
                  <span class="room-entry-name"><?= htmlspecialchars($room['nama']) ?></span>
                  <span class="room-entry-meta">
                    <?= $room['ukuran_m2'] ? $room['ukuran_m2'] . ' m² · ' : '' ?>
                    <?= $room['max_tamu'] ?> tamu ·
                    Rp <?= number_format($room['harga_malam'], 0, ',', '.') ?>/malam
                  </span>
                </div>
                <div class="room-entry-actions">
                  <button class="room-edit-btn" onclick="editRoom(<?= $i ?>)">
                    <i class="ph-bold ph-pencil-simple"></i> Edit
                  </button>
                  <button class="room-delete-btn" onclick="deleteRoom(<?= $i ?>)">
                    <i class="ph-bold ph-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Tombol tambah kamar -->
      <button class="add-room-btn" id="btnTambahKamar">
        <i class="ph-bold ph-plus-circle"></i>
        Tambah Tipe Kamar
      </button>

      <!-- Form tambah/edit kamar (tersembunyi awalnya) -->
      <div id="roomFormCard" class="room-form-card" style="display:none;">
        <div class="room-form-header">
          <h3 id="roomFormTitle">Tambah Tipe Kamar</h3>
          <button class="room-form-close" id="btnTutupForm">
            <i class="ph-bold ph-x"></i>
          </button>
        </div>

        <div class="room-form-body">
          <input type="hidden" id="editIndex" value="-1" />

          <div class="form-row two-col">
            <div class="form-group">
              <label for="roomNama">Nama Kamar <span class="required">*</span></label>
              <input type="text" id="roomNama" placeholder="cth: Kamar Standar, Suite Deluxe..." maxlength="100" />
            </div>
            <div class="form-group">
              <label for="roomHarga">Harga per Malam (Rp) <span class="required">*</span></label>
              <input type="number" id="roomHarga" placeholder="cth: 850000" min="0" />
            </div>
          </div>

          <div class="form-row two-col">
            <div class="form-group">
              <label for="roomUkuran">Ukuran Kamar (m²)</label>
              <input type="number" id="roomUkuran" placeholder="cth: 24" min="1" />
            </div>
            <div class="form-group">
              <label for="roomMaxTamu">Kapasitas Tamu <span class="required">*</span></label>
              <div class="counter-input">
                <button type="button" class="counter-btn" id="btnTamuMin">
                  <i class="ph-bold ph-minus"></i>
                </button>
                <span id="tamuCount">2</span>
                <button type="button" class="counter-btn" id="btnTamuPlus">
                  <i class="ph-bold ph-plus"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="roomDeskripsi">Deskripsi Singkat</label>
            <textarea id="roomDeskripsi" rows="2" maxlength="300"
              placeholder="cth: Kamar nyaman dengan kasur king size dan pemandangan taman..."></textarea>
          </div>

          <div class="form-group">
            <label>Fasilitas Kamar</label>
            <div class="room-facilities-grid">
              <?php
              $fasilitas_kamar = [
                'Kasur Twin'       => 'ph-bed',
                'Kasur Double'     => 'ph-bed',
                'Kasur King'       => 'ph-bed',
                'Kamar Mandi Dalam'=> 'ph-shower',
                'Bathtub'          => 'ph-bathtub',
                'TV LED'           => 'ph-television',
                'Minibar'          => 'ph-wine',
                'Balkon'           => 'ph-door-open',
                'AC'               => 'ph-snowflake',
                'Brankas'          => 'ph-lock-key',
                'Meja Kerja'       => 'ph-desk',
                'Sofa'             => 'ph-armchair',
              ];
              foreach ($fasilitas_kamar as $nama => $icon): ?>
                <label class="facility-chip">
                  <input type="checkbox" name="room_fasilitas[]" value="<?= $nama ?>" />
                  <span><i class="ph-bold <?= $icon ?>"></i> <?= $nama ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div id="roomFormError" class="form-error" style="display:none;"></div>

          <div class="room-form-footer">
            <button type="button" class="cancel-btn" id="btnBatalForm">Batal</button>
            <button type="button" class="save-room-btn" id="btnSimpanKamar">
              <i class="ph-bold ph-floppy-disk"></i> Simpan Kamar
            </button>
          </div>
        </div>
      </div>

      <p id="roomsError" class="rooms-error" style="display:none;">
        <i class="ph-bold ph-warning"></i> Tambahkan minimal 1 tipe kamar sebelum melanjutkan.
      </p>
    </main>

    <footer class="onboarding-footer">
      <div class="progress-bar">
        <div class="progress-segment completed"></div>
        <div class="progress-segment completed"></div>
        <div class="progress-segment"></div>
      </div>
      <div class="footer-actions">
        <a href="amenities.php" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
      </div>
    </footer>

    <script>
      // ── State ─────────────────────────────────────────────
      let rooms = <?= json_encode($saved_rooms) ?>;
      let tamuCount = 2;

      // ── Counter tamu ──────────────────────────────────────
      document.getElementById('btnTamuMin').addEventListener('click', () => {
        if (tamuCount > 1) { tamuCount--; updateTamuDisplay(); }
      });
      document.getElementById('btnTamuPlus').addEventListener('click', () => {
        if (tamuCount < 20) { tamuCount++; updateTamuDisplay(); }
      });
      function updateTamuDisplay() {
        document.getElementById('tamuCount').textContent = tamuCount;
      }

      // ── Buka/tutup form ───────────────────────────────────
      document.getElementById('btnTambahKamar').addEventListener('click', () => openForm(-1));
      document.getElementById('btnTutupForm').addEventListener('click', closeForm);
      document.getElementById('btnBatalForm').addEventListener('click', closeForm);

      function openForm(index) {
        document.getElementById('editIndex').value = index;
        document.getElementById('roomFormTitle').textContent =
          index === -1 ? 'Tambah Tipe Kamar' : 'Edit Tipe Kamar';
        document.getElementById('roomFormError').style.display = 'none';

        // Reset
        document.getElementById('roomNama').value     = '';
        document.getElementById('roomHarga').value    = '';
        document.getElementById('roomUkuran').value   = '';
        document.getElementById('roomDeskripsi').value= '';
        tamuCount = 2;
        updateTamuDisplay();
        document.querySelectorAll('input[name="room_fasilitas[]"]').forEach(cb => cb.checked = false);

        // Fill kalau edit
        if (index >= 0 && rooms[index]) {
          const r = rooms[index];
          document.getElementById('roomNama').value      = r.nama      || '';
          document.getElementById('roomHarga').value     = r.harga_malam || '';
          document.getElementById('roomUkuran').value    = r.ukuran_m2 || '';
          document.getElementById('roomDeskripsi').value = r.deskripsi  || '';
          tamuCount = r.max_tamu || 2;
          updateTamuDisplay();
          if (Array.isArray(r.fasilitas)) {
            r.fasilitas.forEach(f => {
              const cb = document.querySelector(`input[name="room_fasilitas[]"][value="${f}"]`);
              if (cb) cb.checked = true;
            });
          }
        }

        document.getElementById('roomFormCard').style.display = 'block';
        document.getElementById('roomFormCard').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }

      function closeForm() {
        document.getElementById('roomFormCard').style.display = 'none';
      }

      // ── Simpan kamar ke array ─────────────────────────────
      document.getElementById('btnSimpanKamar').addEventListener('click', () => {
        const nama  = document.getElementById('roomNama').value.trim();
        const harga = parseFloat(document.getElementById('roomHarga').value);
        const errEl = document.getElementById('roomFormError');

        if (!nama) {
          errEl.textContent = 'Nama kamar wajib diisi.';
          errEl.style.display = 'flex';
          return;
        }
        if (!harga || harga <= 0) {
          errEl.textContent = 'Harga per malam wajib diisi.';
          errEl.style.display = 'flex';
          return;
        }
        errEl.style.display = 'none';

        const fasilitas = [...document.querySelectorAll('input[name="room_fasilitas[]"]:checked')]
          .map(cb => cb.value);

        const room = {
          nama:        nama,
          deskripsi:   document.getElementById('roomDeskripsi').value.trim(),
          ukuran_m2:   parseInt(document.getElementById('roomUkuran').value) || null,
          max_tamu:    tamuCount,
          harga_malam: harga,
          fasilitas:   fasilitas,
        };

        const idx = parseInt(document.getElementById('editIndex').value);
        if (idx >= 0) {
          rooms[idx] = room;
        } else {
          rooms.push(room);
        }

        renderRooms();
        closeForm();
        document.getElementById('roomsError').style.display = 'none';
      });

      // ── Edit & Delete ─────────────────────────────────────
      function editRoom(index)   { openForm(index); }
      function deleteRoom(index) {
        if (confirm('Hapus kamar ini?')) {
          rooms.splice(index, 1);
          renderRooms();
        }
      }

      // ── Render list kamar ─────────────────────────────────
      function renderRooms() {
        const list = document.getElementById('roomsList');
        list.innerHTML = '';
        rooms.forEach((r, i) => {
          const meta = [
            r.ukuran_m2 ? r.ukuran_m2 + ' m²' : null,
            r.max_tamu + ' tamu',
            'Rp ' + Number(r.harga_malam).toLocaleString('id-ID') + '/malam',
          ].filter(Boolean).join(' · ');

          list.innerHTML += `
            <div class="room-entry" data-index="${i}">
              <div class="room-entry-header">
                <div class="room-entry-info">
                  <span class="room-entry-name">${escHtml(r.nama)}</span>
                  <span class="room-entry-meta">${meta}</span>
                </div>
                <div class="room-entry-actions">
                  <button class="room-edit-btn" onclick="editRoom(${i})">
                    <i class="ph-bold ph-pencil-simple"></i> Edit
                  </button>
                  <button class="room-delete-btn" onclick="deleteRoom(${i})">
                    <i class="ph-bold ph-trash"></i>
                  </button>
                </div>
              </div>
            </div>`;
        });
      }

      function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
      }

      // ── Selanjutnya → simpan & lanjut ke policies ─────────
      document.getElementById('btnSelanjutnya').addEventListener('click', () => {
        if (rooms.length === 0) {
          document.getElementById('roomsError').style.display = 'flex';
          return;
        }

        fetch('save_rooms.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ rooms })
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            window.location.href = 'policies.php';
          } else {
            alert('Gagal menyimpan: ' + (data.message || 'Error tidak diketahui'));
          }
        })
        .catch(() => alert('Terjadi kesalahan jaringan.'));
      });
    </script>
  </body>
</html>