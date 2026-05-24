<?php
session_start();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lokasi Properti | Teman Singgah</title>
  <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />

  <link rel="stylesheet" href="../../../components/root.css" />
  <link rel="stylesheet" href="../../../components/navbar.css" />
  <link rel="stylesheet" href="../onboarding.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

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
      <h2>Di mana lokasi properti Anda?</h2>
      <p>Alamat lengkap Anda hanya akan dibagikan setelah tamu melakukan pemesanan.</p>
    </div>

    <div class="location-search">
      <i class="ph-fill ph-map-pin"></i>
      <input type="text" id="alamatInput" placeholder="Masukkan alamat lengkap"
        value="<?= isset($_SESSION['onboarding']['lokasi']) ? htmlspecialchars($_SESSION['onboarding']['lokasi']) : '' ?>" />
    </div>

    <div class="map-container">
      <div id="propertyMap" class="map-body">
        <div class="custom-zoom-control">
          <button class="custom-zoom-button" id="zoomIn"><i class="ph-bold ph-plus"></i></button>
          <button class="custom-zoom-button" id="zoomOut"><i class="ph-bold ph-minus"></i></button>
        </div>
      </div>
    </div>
  </main>

  <footer class="onboarding-footer">
    <div class="progress-bar">
      <div class="progress-segment completed"></div>
      <div class="progress-segment"></div>
      <div class="progress-segment"></div>
    </div>
    <div class="footer-actions">
      <a href="privacy_type.php" class="back-button">Kembali</a>
      <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
    </div>
  </footer>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const savedLat = <?= isset($_SESSION['onboarding']['latitude']) ? $_SESSION['onboarding']['latitude'] : '-7.9797' ?>;
    const savedLng = <?= isset($_SESSION['onboarding']['longitude']) ? $_SESSION['onboarding']['longitude'] : '112.6304' ?>;
    const savedAlamat = <?= isset($_SESSION['onboarding']['lokasi']) ? json_encode($_SESSION['onboarding']['lokasi']) : '""' ?>;

    const map = L.map('propertyMap', {
      center: [savedLat, savedLng],
      zoom: 15,
      scrollWheelZoom: false,
      zoomControl: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
      maxZoom: 19,
    }).addTo(map);

    const icon = L.divIcon({
      html: `<i class="ph-fill ph-map-pin" style="font-size:40px;color:#8b2500;display:block;cursor:pointer;"></i>`,
      iconSize: [40, 40],
      iconAnchor: [20, 40],
      className: '',
    });

    const marker = L.marker([savedLat, savedLng], { icon }).addTo(map);

    const alamatInput = document.getElementById('alamatInput');
    if (savedAlamat) alamatInput.value = savedAlamat;

    let currentLat = savedLat;
    let currentLng = savedLng;

    // Reverse geocode helper
    async function reverseGeocode(lat, lng) {
      try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
        const data = await res.json();
        if (data && data.display_name) {
          alamatInput.value = data.display_name;
        }
      } catch (err) { }
    }

    // Klik peta → pindah marker + isi alamat (sama persis dengan versi HTML)
    let clickTimer = null;
    map.on('click', (e) => {
      if (clickTimer) return;
      clickTimer = setTimeout(() => {
        clickTimer = null;
        currentLat = e.latlng.lat;
        currentLng = e.latlng.lng;
        marker.setLatLng(e.latlng);
        reverseGeocode(currentLat, currentLng);
      }, 250);
    });

    map.on('dblclick', () => {
      if (clickTimer) { clearTimeout(clickTimer); clickTimer = null; }
      map.zoomIn();
    });

    // Zoom buttons
    L.DomEvent.on(document.getElementById('zoomIn'), 'click', (e) => {
      L.DomEvent.stopPropagation(e); map.zoomIn();
    });
    L.DomEvent.on(document.getElementById('zoomOut'), 'click', (e) => {
      L.DomEvent.stopPropagation(e); map.zoomOut();
    });

    // Ketik alamat → pindah marker
    alamatInput.addEventListener('blur', function () {
      const alamat = this.value;
      if (!alamat) return;
      fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(alamat)}`)
        .then(res => res.json())
        .then(results => {
          if (results.length > 0) {
            currentLat = parseFloat(results[0].lat);
            currentLng = parseFloat(results[0].lon);
            map.setView([currentLat, currentLng], 16);
            marker.setLatLng([currentLat, currentLng]);
          }
        });
    });

    alamatInput.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      const alamat = this.value;
      if (!alamat) return;
      fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(alamat)}`)
        .then(res => res.json())
        .then(results => {
          if (results.length > 0) {
            currentLat = parseFloat(results[0].lat);
            currentLng = parseFloat(results[0].lon);
            map.setView([currentLat, currentLng], 16);
            marker.setLatLng([currentLat, currentLng]);
          }
        });
    });

    // Simpan & lanjut
    document.getElementById('btnSelanjutnya').addEventListener('click', function () {
      const lokasi = alamatInput.value.trim();
      if (!lokasi) {
        alert('Masukkan alamat properti dulu ya!');
        return;
      }
      fetch('save_location.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ lokasi, latitude: currentLat, longitude: currentLng })
      })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') window.location.href = 'floor_plan.php';
        });
    });
  </script>
</body>

</html>