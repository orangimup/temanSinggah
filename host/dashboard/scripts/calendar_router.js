/**
 * calendar_router.js
 * Modul kalender host — mengelola tampilan bulan, detail hari, & aksi blokir.
 */

// ─── State ────────────────────────────────────────────────────────────────
let activeListingId   = null;
let currentMonth      = new Date().getMonth() + 1; // 1-12
let currentYear       = new Date().getFullYear();
let calendarData      = {};   // { "YYYY-MM-DD": { status, price, stok_total, stok_tersisa } }
let isDetailMode      = false; // apakah sidebar sedang menampilkan detail hari

const API = '/host/pages/calendar_router.php';

// ─── Inisialisasi ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadListings();
    bindNavButtons();
});

// ─── Muat daftar listing ke sidebar ───────────────────────────────────────
async function loadListings() {
    try {
        const res  = await fetch(`${API}?action=get_listings`);
        const data = await res.json();

        if (data.status !== 'success' || !data.listings.length) {
            showSidebarMessage('Tidak ada listing ditemukan.');
            return;
        }

        renderListingSidebar(data.listings);

        // Set listing aktif pertama
        activeListingId = data.listings[0].id;
        highlightActiveListing(activeListingId);
        loadCalendar();
    } catch (err) {
        console.error('loadListings error:', err);
        showSidebarMessage('Gagal memuat listing.');
    }
}

// ─── Render daftar listing di sidebar ────────────────────────────────────
function renderListingSidebar(listings) {
    const sidebar = document.getElementById('listing-sidebar');
    if (!sidebar) return;

    sidebar.innerHTML = `
        <div class="sidebar-listing-list" id="sidebar-listing-list">
            <h3 class="sidebar-title">Listing Saya</h3>
            ${listings.map(l => `
                <div class="sidebar-listing-item" data-id="${l.id}" onclick="selectListing(${l.id})">
                    ${l.foto_utama
                        ? `<img src="${escHtml(l.foto_utama)}" alt="${escHtml(l.judul)}" class="sidebar-listing-thumb">`
                        : `<div class="sidebar-listing-thumb placeholder-thumb"></div>`}
                    <span class="sidebar-listing-name">${escHtml(l.judul)}</span>
                </div>
            `).join('')}
        </div>
    `;
}

// ─── Pilih listing & reload kalender ─────────────────────────────────────
function selectListing(id) {
    activeListingId = id;
    isDetailMode    = false;
    highlightActiveListing(id);
    loadCalendar();
}

function highlightActiveListing(id) {
    document.querySelectorAll('.sidebar-listing-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.id) === id);
    });
}

// ─── Navigasi bulan ───────────────────────────────────────────────────────
function bindNavButtons() {
    document.getElementById('btn-prev-month')?.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 1) { currentMonth = 12; currentYear--; }
        loadCalendar();
    });
    document.getElementById('btn-next-month')?.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 12) { currentMonth = 1; currentYear++; }
        loadCalendar();
    });
}

// ─── Muat kalender dari API ───────────────────────────────────────────────
async function loadCalendar() {
    if (!activeListingId) return;

    setCalendarLoading(true);
    try {
        const url = `${API}?action=get_calendar&listing_id=${activeListingId}&month=${currentMonth}&year=${currentYear}`;
        const res  = await fetch(url);
        const data = await res.json();

        if (data.status !== 'success') {
            console.error('loadCalendar:', data.message);
            setCalendarLoading(false);
            return;
        }

        calendarData = data.calendar;
        updateMonthLabel();
        renderCalendar();
    } catch (err) {
        console.error('loadCalendar error:', err);
    } finally {
        setCalendarLoading(false);
    }
}

// ─── Render grid kalender ─────────────────────────────────────────────────
function renderCalendar() {
    const grid = document.getElementById('calendar-grid');
    if (!grid) return;

    const firstDay    = new Date(currentYear, currentMonth - 1, 1).getDay(); // 0=Minggu
    const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();

    let html = '';

    // Header hari
    ['Min','Sen','Sel','Rab','Kam','Jum','Sab'].forEach(d => {
        html += `<div class="calendar-header-cell">${d}</div>`;
    });

    // Sel kosong sebelum hari pertama
    for (let i = 0; i < firstDay; i++) {
        html += `<div class="day-card day-empty"></div>`;
    }

    // Hari-hari dalam bulan
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${currentYear}-${String(currentMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const info    = calendarData[dateStr] || { status: 'available', price: 0, stok_total: 0, stok_tersisa: 0 };
        const cls     = dayClass(info.status);
        const price   = info.price ? formatRupiah(info.price) : '';
        const stok    = info.stok_tersisa;
        const today   = isToday(dateStr) ? ' day-today' : '';

        html += `
            <div class="day-card ${cls}${today}" data-date="${dateStr}" onclick="handleDayClick('${dateStr}')">
                <span class="day-number">${d}</span>
                ${price ? `<span class="day-price">${price}</span>` : ''}
                ${stok > 0 && info.status !== 'blocked' && info.status !== 'booked'
                    ? `<span class="stok-badge">${stok}</span>`
                    : ''}
            </div>
        `;
    }

    grid.innerHTML = html;
}

function dayClass(status) {
    switch (status) {
        case 'blocked':   return 'day-blocked';
        case 'booked':    return 'day-booked';
        case 'partial':   return 'day-partial';
        case 'available': return 'day-available';
        default:          return '';
    }
}

// ─── Klik pada hari — ambil detail & tampilkan di sidebar ─────────────────
async function handleDayClick(dateStr) {
    if (!activeListingId) return;

    // Highlight day card
    document.querySelectorAll('.day-card').forEach(el => el.classList.remove('day-selected'));
    document.querySelector(`.day-card[data-date="${dateStr}"]`)?.classList.add('day-selected');

    try {
        const url = `${API}?action=get_day_detail&listing_id=${activeListingId}&date=${dateStr}`;
        const res  = await fetch(url);
        const data = await res.json();

        if (data.status !== 'success') return;
        renderDayDetail(data);
        isDetailMode = true;
    } catch (err) {
        console.error('handleDayClick error:', err);
    }
}

// ─── Render panel detail hari di sidebar ─────────────────────────────────
function renderDayDetail(data) {
    const sidebar = document.getElementById('listing-sidebar');
    if (!sidebar) return;

    const statusLabel = {
        available: '<span class="status-pill status-available">Tersedia</span>',
        blocked:   '<span class="status-pill status-blocked">Diblokir</span>',
        booked:    '<span class="status-pill status-booked">Penuh</span>',
        partial:   '<span class="status-pill status-partial">Sebagian</span>',
    }[data.day_status] ?? data.day_status;

    const bookingRows = data.bookings.length
        ? data.bookings.map(b => `
            <div class="detail-booking-row">
                <div class="detail-booking-guest">${escHtml(b.nama_tamu)}</div>
                <div class="detail-booking-dates">${b.checkin} → ${b.checkout}</div>
                <div class="detail-booking-meta">
                    <span class="booking-status-badge booking-${b.status}">${b.status}</span>
                    <span class="detail-booking-guests">${b.jumlah_tamu} tamu</span>
                </div>
            </div>
        `).join('')
        : '<p class="detail-empty">Tidak ada booking pada tanggal ini.</p>';

    const blockedInfo = data.blocked_info
        ? `<div class="detail-blocked-info">
               <span class="icon-blocked">🔒</span>
               Alasan blokir: <strong>${escHtml(data.blocked_info.reason)}</strong>
           </div>`
        : '';

    const canBlock = data.day_status !== 'blocked';

    sidebar.innerHTML = `
        <div class="day-detail-panel">
            <button class="btn-back-listing" onclick="backToListings()">← Kembali ke Listing</button>

            <div class="detail-header">
                <h3 class="detail-date">${formatDateId(data.date)}</h3>
                ${statusLabel}
            </div>

            ${blockedInfo}

            <div class="detail-price-row">
                <span class="detail-label">Harga malam ini</span>
                <span class="detail-price-value">${data.harga ? formatRupiah(data.harga) : '—'}</span>
            </div>

            <div class="detail-stok-row">
                <span class="detail-label">Sisa kamar</span>
                <span class="detail-stok-value">${data.stok_tersisa} / ${data.stok_total}</span>
            </div>

            <div class="detail-section">
                <h4 class="detail-section-title">Booking (${data.bookings.length})</h4>
                <div class="detail-bookings-list">${bookingRows}</div>
            </div>

            <div class="detail-actions">
                ${canBlock
                    ? `<button class="btn-block-date" onclick="blockDate('${data.date}')">🔒 Blokir Tanggal</button>`
                    : `<button class="btn-unblock-date" onclick="unblockDate('${data.date}')">🔓 Buka Blokir</button>`
                }
                <button class="btn-edit-price" onclick="promptEditPrice('${data.date}', ${data.harga || 0})">
                    ✏️ Ubah Harga
                </button>
            </div>
        </div>
    `;
}

// ─── Kembali ke daftar listing ────────────────────────────────────────────
function backToListings() {
    isDetailMode = false;
    document.querySelectorAll('.day-card').forEach(el => el.classList.remove('day-selected'));
    loadListings(); // re-render sidebar listing list
}

// ─── Block date ───────────────────────────────────────────────────────────
async function blockDate(dateStr, reason = 'manual', roomId = null) {
    if (!activeListingId) return;

    const confirmMsg = `Blokir tanggal ${formatDateId(dateStr)}?`;
    if (!confirm(confirmMsg)) return;

    try {
        const body = { listing_id: activeListingId, date: dateStr, reason };
        if (roomId) body.room_id = roomId;

        const res  = await fetch(`${API}?action=block_date`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        });
        const data = await res.json();

        alert(data.message);
        if (data.status === 'success' || data.status === 'info') {
            await loadCalendar();
            handleDayClick(dateStr); // refresh detail panel
        }
    } catch (err) {
        console.error('blockDate error:', err);
        alert('Terjadi kesalahan saat memblokir tanggal.');
    }
}

// ─── Unblock date ─────────────────────────────────────────────────────────
async function unblockDate(dateStr, roomId = null) {
    if (!activeListingId) return;

    if (!confirm(`Buka blokir tanggal ${formatDateId(dateStr)}?`)) return;

    try {
        const body = { listing_id: activeListingId, date: dateStr };
        if (roomId) body.room_id = roomId;

        const res  = await fetch(`${API}?action=unblock_date`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        });
        const data = await res.json();

        alert(data.message);
        await loadCalendar();
        handleDayClick(dateStr);
    } catch (err) {
        console.error('unblockDate error:', err);
        alert('Terjadi kesalahan saat membuka blokir.');
    }
}

// ─── Update harga via prompt ───────────────────────────────────────────────
async function promptEditPrice(dateStr, currentPrice) {
    const input = prompt(`Harga baru untuk ${formatDateId(dateStr)} (sekarang: ${formatRupiah(currentPrice)}):`, currentPrice);
    if (input === null) return;
    const price = parseFloat(input);
    if (isNaN(price) || price <= 0) { alert('Harga tidak valid.'); return; }

    try {
        const res  = await fetch(`${API}?action=update_price`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ listing_id: activeListingId, date: dateStr, custom_price: price }),
        });
        const data = await res.json();
        alert(data.message);
        if (data.status === 'success') {
            await loadCalendar();
            handleDayClick(dateStr);
        }
    } catch (err) {
        console.error('promptEditPrice error:', err);
        alert('Gagal memperbarui harga.');
    }
}

// ─── UI helpers ───────────────────────────────────────────────────────────
function updateMonthLabel() {
    const label = document.getElementById('calendar-month-label');
    if (!label) return;
    const names = ['Januari','Februari','Maret','April','Mei','Juni',
                   'Juli','Agustus','September','Oktober','November','Desember'];
    label.textContent = `${names[currentMonth - 1]} ${currentYear}`;
}

function setCalendarLoading(on) {
    const grid = document.getElementById('calendar-grid');
    if (!grid) return;
    grid.classList.toggle('calendar-loading', on);
}

function showSidebarMessage(msg) {
    const sidebar = document.getElementById('listing-sidebar');
    if (sidebar) sidebar.innerHTML = `<p class="sidebar-msg">${escHtml(msg)}</p>`;
}

function isToday(dateStr) {
    return dateStr === new Date().toISOString().slice(0, 10);
}

function formatRupiah(angka) {
    return 'Rp ' + Math.round(angka).toLocaleString('id-ID');
}

function formatDateId(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}