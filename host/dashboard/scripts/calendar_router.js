// State management
const state = {
    currentMonth: new Date().getMonth(),
    currentYear: new Date().getFullYear(),
    viewMode: 'month',
    listingId: null,
    roomId: null
};

// Constants
const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
const dayNames = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

// Data from database (will be injected from PHP)
let calendarData = {
    manualBlocked: {},
    autoBlocked: {},
    bookedDates: {},
    customPrices: {},
    settings: {}
};

// Initialize data from PHP
function initCalendarData(data) {
    calendarData = data;
    state.listingId = data.listingId;
    state.roomId = data.roomId;
}

// Helper functions
function getDaysInMonth(month, year) {
    return new Date(year, month + 1, 0).getDate();
}

function getFirstDayOfMonth(month, year) {
    return new Date(year, month, 1).getDay();
}

function formatRupiah(angka) {
    if (!angka || isNaN(angka)) return 'Rp0';
    return 'Rp' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function getBasePriceForDate(dateStr) {
    const date = new Date(dateStr);
    const day = date.getDay();
    const isWeekend = (day === 0 || day === 6);
    let price = isWeekend ? calendarData.settings.harga_akhir_pekan : calendarData.settings.harga_malam;
    return parseInt(price) || 0;
}

function getDateStatus(day, month, year) {
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    const today = new Date();
    const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();

    if (isToday) {
        return { status: 'today', label: 'Hari ini', price: null, tooltip: 'Hari ini' };
    }
    if (calendarData.manualBlocked[dateStr]) {
        return { status: 'manual-blocked', label: 'Diblokir', price: null, tooltip: 'Diblokir manual' };
    }
    if (calendarData.autoBlocked[dateStr]) {
        return { status: 'auto-blocked', label: 'Penuh', price: null, tooltip: 'Terkunci otomatis karena kuota penuh' };
    }
    if (calendarData.bookedDates[dateStr]) {
        return { status: 'booked', label: 'Dipesan', price: null, tooltip: 'Sudah dipesan' };
    }

    const customPrice = calendarData.customPrices[dateStr];
    const price = customPrice || getBasePriceForDate(dateStr);
    return { status: 'available', label: formatRupiah(price), price: price, tooltip: formatRupiah(price) };
}

// Render functions
function renderMonthSection(month, year) {
    const totalDays = getDaysInMonth(month, year);
    const firstDay = getFirstDayOfMonth(month, year);

    let html = `<div class="month-section">
        <div class="month-label">${monthNames[month]} ${year}</div>
        <div class="day-grid">`;

    for (let i = 0; i < firstDay; i++) {
        html += `<div class="day-card day-empty"></div>`;
    }

    for (let day = 1; day <= totalDays; day++) {
        const { status, label, tooltip } = getDateStatus(day, month, year);
        let statusClass = '';

        switch (status) {
            case 'today':
                statusClass = 'today';
                break;
            case 'booked':
                statusClass = 'booked';
                break;
            case 'manual-blocked':
                statusClass = 'blocked-manual';
                break;
            case 'auto-blocked':
                statusClass = 'blocked-auto';
                break;
        }

        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        html += `<div class="day-card ${statusClass}" data-date="${dateStr}" data-tooltip="${tooltip}" onclick="handleDayClick(event, '${dateStr}', '${status}', ${day}, ${month}, ${year})">
            <span class="day-number">${day}</span>
            <span class="day-price">${label}</span>
        </div>`;
    }

    html += `</div></div>`;
    return html;
}

function renderCalendar() {
    const container = document.getElementById('calendarContainer');
    if (!container) return;

    let html = `<div class="weekday-header">`;
    for (let i = 0; i < dayNames.length; i++) {
        html += `<div class="weekday-label">${dayNames[i]}</div>`;
    }
    html += `</div>`;

    if (state.viewMode === 'month') {
        html += renderMonthSection(state.currentMonth, state.currentYear);
        container.classList.remove('year-view');
        document.getElementById('currentMonthLabel').textContent = `${monthNames[state.currentMonth]} ${state.currentYear}`;
    } else {
        container.classList.add('year-view');
        document.getElementById('currentMonthLabel').textContent = state.currentYear;
        for (let month = 0; month < 12; month++) {
            html += renderMonthSection(month, state.currentYear);
        }
    }

    container.innerHTML = html;
}

// Date detail popup functions
function showDateDetail(dateStr, status, day, month, year) {
    const popup = document.getElementById('dateDetailPopup');
    if (!popup) return;

    document.getElementById('ddpDate').textContent = `${day} ${monthNames[month]} ${year}`;

    let statusHtml = '';
    let actionsHtml = '';

    switch (status) {
        case 'available':
            statusHtml = `<span style="color:var(--color-success);">Tersedia</span>`;
            actionsHtml = `
        <button onclick="toggleBlock('${dateStr}', 'block')" class="popup-btn block-btn">Kunci Tanggal</button>
        <button onclick="setCustomPriceForDate('${dateStr}')" class="popup-btn price-btn">Atur Harga Khusus</button>
        `;
            break;
        case 'manual-blocked':
            statusHtml = `<span style="color: #ef4444;">Diblokir Manual</span>`;
            actionsHtml = `<button onclick="toggleBlock('${dateStr}', 'unblock')" class="popup-btn unblock-btn">Buka Kunci</button>`;
            break;
        case 'auto-blocked':
            statusHtml = `<span style="color: #f59e0b;">Terkunci Otomatis (Kuota Penuh)</span>`;
            actionsHtml = `<p style="font-size:12px; color:#666; margin-top:8px;">Tanggal ini terkunci otomatis karena sudah mencapai batas maksimum booking.</p>`;
            break;
        case 'booked':
            statusHtml = `<span style="color: #3b82f6;">Sudah Dipesan</span>`;
            actionsHtml = `<p style="font-size:12px; color:#666; margin-top:8px;">Tidak dapat mengubah tanggal yang sudah dipesan.</p>`;
            break;
        case 'today':
            statusHtml = `<span style="color: #10b981;">Hari Ini</span>`;
            actionsHtml = `<p style="font-size:12px; color:#666; margin-top:8px;">Hari ini tidak dapat diubah.</p>`;
            break;
    }

    document.getElementById('ddpStatus').innerHTML = statusHtml;
    document.getElementById('ddpActions').innerHTML = actionsHtml;

    popup.style.display = 'block';
    popup.style.top = '50%';
    popup.style.left = '50%';
    popup.style.transform = 'translate(-50%, -50%)';
}

function closeDatePopup() {
    const popup = document.getElementById('dateDetailPopup');
    if (popup) popup.style.display = 'none';
}

// AJAX functions
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.add('active');
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.remove('active');
}

async function toggleBlock(dateStr, action) {
    showLoading();
    closeDatePopup();

    try {
        const response = await fetch('/teman_singgah/host/dashboard/pages/toggle_block.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                listing_id: state.listingId,
                date: dateStr,
                action: action,
                reason: 'manual',
                room_id: state.roomId
            })
        });
        const result = await response.json();
        if (result.success) {
            window.location.reload();
        } else {
            alert('Gagal: ' + (result.message || 'Terjadi kesalahan'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
    hideLoading();
}

async function setCustomPriceForDate(dateStr) {
    // Buat modal
    const modal = document.createElement('div');
    modal.style.cssText = `position:fixed;inset:0;background:var(--color-bg-overlay);z-index:2000;display:flex;align-items:center;justify-content:center;`;
    modal.innerHTML = `
    <div style="background:var(--color-bg-card);border-radius:var(--radius-2xl);padding:24px;width:320px;box-shadow:var(--shadow-dropdown);">
        <h3 style="margin:0 0 8px;font-size:var(--text-md);font-weight:var(--font-bold);color:var(--color-text-primary);">Atur Harga Khusus</h3>
        <p style="margin:0 0 16px;font-size:var(--text-sm);color:var(--color-text-secondary);">${dateStr}</p>
        <div style="display:flex;align-items:center;border:1.5px solid var(--color-border);border-radius:var(--radius-xl);padding:12px 16px;margin-bottom:16px;">
            <span style="font-size:var(--text-md);font-weight:var(--font-bold);color:var(--color-text-primary);margin-right:4px;">Rp</span>
            <input id="customPriceInput" type="number" placeholder="0" min="0"
                style="border:none;outline:none;font-size:var(--text-md);font-weight:var(--font-bold);width:100%;font-family:var(--font-family);background:transparent;color:var(--color-text-primary);" />
        </div>
        <div style="display:flex;gap:8px;">
            <button id="cancelPriceBtn" style="flex:1;padding:12px;border:1.5px solid var(--color-border);border-radius:var(--radius-xl);background:var(--color-bg-card);cursor:pointer;font-size:var(--text-sm);font-weight:var(--font-semibold);font-family:var(--font-family);color:var(--color-text-secondary);">Batal</button>
            <button id="savePriceBtn" style="flex:1;padding:12px;border:none;border-radius:var(--radius-xl);background:var(--color-primary);color:var(--color-text-inverse);cursor:pointer;font-size:var(--text-sm);font-weight:var(--font-semibold);font-family:var(--font-family);">Simpan</button>
        </div>
    </div>
`;
    document.body.appendChild(modal);
    document.getElementById('customPriceInput').focus();

    return new Promise((resolve) => {
        document.getElementById('cancelPriceBtn').onclick = () => {
            modal.remove();
            resolve(null);
        };
        document.getElementById('savePriceBtn').onclick = async () => {
            const newPrice = document.getElementById('customPriceInput').value;
            modal.remove();
            if (newPrice && !isNaN(newPrice) && parseInt(newPrice) >= 0) {
                showLoading();
                try {
                    const response = await fetch('/teman_singgah/host/dashboard/pages/set_custom_price.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            listing_id: state.listingId,
                            date: dateStr,
                            price: parseInt(newPrice),
                            room_id: state.roomId
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.location.reload();
                    } else {
                        alert('Gagal mengatur harga');
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                }
                hideLoading();
            }
            resolve(null);
        };
    });
}

async function saveSetting(field, value) {
    showLoading();
    try {
        const response = await fetch('/teman_singgah/host/dashboard/pages/save_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                listing_id: state.listingId,
                field: field,
                value: value
            })
        });
        const result = await response.json();
        if (!result.success) {
            alert('Gagal menyimpan pengaturan');
        } else {
            if (field === 'harga_malam') calendarData.settings.harga_malam = value;
            if (field === 'harga_akhir_pekan') calendarData.settings.harga_akhir_pekan = value;
            renderCalendar();
        }
    } catch (error) {
        console.error('Error:', error);
    }
    hideLoading();
}

// Panel handlers
function openPanel(panelId) {
    const panel = document.getElementById(panelId);
    if (panel) panel.classList.add('open');
}

function closePanel(panelId) {
    const panel = document.getElementById(panelId);
    if (panel) panel.classList.remove('open');
}

// Dropdown handlers
function showDropdown(target, optionsHtml, callback) {
    let popup = document.getElementById('dynamicDropdown');
    if (!popup) {
        popup = document.createElement('div');
        popup.id = 'dynamicDropdown';
        popup.className = 'dropdown-popup';
        document.body.appendChild(popup);
    }

    popup.innerHTML = optionsHtml;
    const rect = target.getBoundingClientRect();
    popup.style.top = `${rect.bottom + window.scrollY + 8}px`;
    popup.style.left = `${rect.left + window.scrollX}px`;
    popup.classList.add('open');

    popup.querySelectorAll('.dropdown-option').forEach(opt => {
        opt.onclick = (e) => {
            e.stopPropagation();
            callback(opt.dataset.mode || opt.dataset.value);
            popup.classList.remove('open');
        };
    });

    document.addEventListener('click', function closeDropdown(e) {
        if (!popup.contains(e.target) && e.target !== target) {
            popup.classList.remove('open');
            document.removeEventListener('click', closeDropdown);
        }
    });
}

function initEventListeners() {
    // Month/year selector
    const monthYearSelector = document.getElementById('monthYearSelector');
    if (monthYearSelector) {
        monthYearSelector.addEventListener('click', (e) => {
            e.stopPropagation();
            let options = '';
            if (state.viewMode === 'month') {
                options = monthNames.map((m, i) =>
                    `<button class="dropdown-option" data-value="${i}">${m}</button>`
                ).join('');
                showDropdown(e.currentTarget, options, (val) => {
                    state.currentMonth = parseInt(val);
                    renderCalendar();
                });
            } else {
                const years = Array.from({ length: 5 }, (_, i) => state.currentYear - 2 + i);
                options = years.map(y =>
                    `<button class="dropdown-option" data-value="${y}">${y}</button>`
                ).join('');
                showDropdown(e.currentTarget, options, (val) => {
                    state.currentYear = parseInt(val);
                    renderCalendar();
                });
            }
        });
    }

    // ↓ TAMBAHKAN DI SINI ↓

    // Listing sidebar items
    document.querySelectorAll('.listing-sidebar-item').forEach(item => {
        item.addEventListener('click', () => {
            document.querySelectorAll('.listing-sidebar-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            const listingId = item.dataset.listingId;
            if (listingId) changeListing(listingId);
        });
    });

    // Close listing sidebar
    const listingSidebarClose = document.querySelector('.listing-sidebar-close');
    if (listingSidebarClose) {
        listingSidebarClose.addEventListener('click', () => {
            document.getElementById('listingSidebar').classList.remove('open');
        });
    }

    const listingSidebar = document.getElementById('listingSidebar');
    if (listingSidebar) {
        listingSidebar.addEventListener('click', (e) => e.stopPropagation());
    }

    // Panel group - buka/tutup settings popup
    document.querySelectorAll('.panel-group').forEach(group => {
        const btn = group.querySelector('.panel-card.button');
        const popup = group.querySelector('.panel-popup');
        if (btn && popup) {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.panel-popup').forEach(p => p.classList.remove('open'));
                popup.classList.add('open');
            });
            popup.querySelector('.popup-header i')?.addEventListener('click', () => {
                popup.classList.remove('open');
            });
        }
    });

    const viewModeBtn = document.getElementById('viewModeBtn');

    if (viewModeBtn) {
        viewModeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const options = `<button class="dropdown-option" data-mode="month">Bulan</button>
                             <button class="dropdown-option" data-mode="year">Tahun</button>`;
            showDropdown(e.currentTarget, options, (val) => {
                state.viewMode = val;
                const viewModeText = document.getElementById('viewModeText');
                if (viewModeText) viewModeText.textContent = state.viewMode === 'month' ? 'Bulan' : 'Tahun';
                renderCalendar();
            });
        });
    }

    // Close popup when clicking outside
    document.addEventListener('click', (e) => {
        const datePopup = document.getElementById('dateDetailPopup');
        if (datePopup && datePopup.style.display === 'block') {
            if (!datePopup.contains(e.target)) {
                closeDatePopup();
            }
        }
    });
}

// Editable fields setup
function initEditableFields() {
    document.querySelectorAll('.panel-button.editable').forEach(btn => {
        const input = btn.querySelector('.panel-input');
        const valueSpan = btn.querySelector('.panel-value');

        let saveBtn = btn.nextElementSibling;
        if (!saveBtn || !saveBtn.classList.contains('panel-save-button')) {
            saveBtn = document.createElement('button');
            saveBtn.className = 'panel-save-button';
            saveBtn.textContent = 'Simpan';
            btn.insertAdjacentElement('afterend', saveBtn);
        }

        const field = btn.dataset.field;

        btn.addEventListener('click', (e) => {
            if (e.target === input || e.target === saveBtn) return;

            document.querySelectorAll('.panel-button.editable.editing').forEach(other => {
                if (other !== btn) closeEdit(other);
            });

            btn.classList.add('editing');
            const editWrap = btn.querySelector('.panel-edit-wrap');
            if (editWrap) editWrap.style.display = 'flex';
            saveBtn.classList.add('visible');
            if (input) {
                input.focus();
                input.select();
            }
        });

        if (input) {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') saveEdit(btn, saveBtn, field);
                if (e.key === 'Escape') closeEdit(btn, saveBtn);
            });
        }

        saveBtn.addEventListener('click', () => saveEdit(btn, saveBtn, field));
    });
}

function closeEdit(btn, saveBtn) {
    btn.classList.remove('editing');
    const editWrap = btn.querySelector('.panel-edit-wrap');
    if (editWrap) editWrap.style.display = 'none';
    if (saveBtn) saveBtn.classList.remove('visible');
}

function saveEdit(btn, saveBtn, field) {
    const input = btn.querySelector('.panel-input');
    const valueSpan = btn.querySelector('.panel-value');
    const prefix = btn.dataset.prefix || '';
    const suffix = btn.dataset.suffix || '';

    let rawValue = input.value;
    let displayValue = rawValue;

    if (prefix === 'Rp') {
        displayValue = formatRupiah(parseInt(rawValue));
        rawValue = parseInt(rawValue);
    } else if (suffix) {
        displayValue = rawValue + suffix;
        rawValue = parseInt(rawValue);
    }

    valueSpan.textContent = displayValue;
    saveSetting(field, rawValue);
    closeEdit(btn, saveBtn);
}

// Change listing
function changeListing(listingId) {
    window.location.href = `calendar_router.php?listing_id=${listingId}`;
}

// Initialize
function initCalendar() {
    initEventListeners();
    initEditableFields();
    renderCalendar();
}

function openListingSidebar() {
    document.getElementById('listingSidebar').classList.add('open');
}
window.openListingSidebar = openListingSidebar;

function handleDayClick(event, dateStr, status, day, month, year) {
    event.stopPropagation();
    openListingSidebar();
    showDateDetail(dateStr, status, day, month, year);
}
window.handleDayClick = handleDayClick;

window.showDateDetail = showDateDetail;
window.closeDatePopup = closeDatePopup;
window.toggleBlock = toggleBlock;
window.setCustomPriceForDate = setCustomPriceForDate;
window.openPanel = openPanel;
window.closePanel = closePanel;
window.changeListing = changeListing;
window.initCalendarData = initCalendarData;
window.initCalendar = initCalendar;

// Panggil setelah DOM siap
if (window.calendarData) {
    initCalendarData(window.calendarData);
}
initCalendar();