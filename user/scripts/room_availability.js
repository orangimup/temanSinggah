(function () {
    const BASE_LISTING_ID = window.BASE_LISTING_ID || 0;
    let lastCheckin  = null;
    let lastCheckout = null;

    function sisaBadge(sisa, stok) {
        if (sisa <= 0)             return `<span class="room-avail-badge penuh">Kamar Penuh</span>`;
        if (sisa === 1)            return `<span class="room-avail-badge sisa-sedikit">Sisa 1 kamar</span>`;
        if (sisa / stok <= 0.4)   return `<span class="room-avail-badge sisa-sedikit">Sisa ${sisa} kamar</span>`;
        return `<span class="room-avail-badge tersedia">Tersedia</span>`;
    }

    function applyAvailability(availability) {
        document.querySelectorAll('.room-card').forEach(card => {
            const roomId = card.dataset.roomId;
            const info   = availability[roomId];
            if (!info) return;

            card.querySelector('.room-avail-badge')?.remove();
            const footer = card.querySelector('.room-card-footer');
            if (footer) footer.insertAdjacentHTML('beforebegin', sisaBadge(info.sisa, info.stok));

            const btn = card.querySelector('.room-book-btn');
            if (!info.tersedia) {
                btn.disabled        = true;
                btn.textContent     = 'Kamar Penuh';
                btn.style.cssText   = 'background:#e5e7eb;color:#9ca3af;cursor:not-allowed;opacity:1;';
                card.style.opacity  = '0.6';

                const roomIdInput = document.getElementById('selectedRoomIdInput');
                if (roomIdInput?.value === roomId) {
                    document.getElementById('clearRoomBtn')?.click();
                }
            } else {
                btn.disabled       = false;
                btn.style.cssText  = '';
                card.style.opacity = '';
                if (!btn.classList.contains('is-selected')) btn.textContent = 'Pilih Kamar';
            }
        });
    }

    function resetRoomCards() {
        document.querySelectorAll('.room-card').forEach(card => {
            card.querySelector('.room-avail-badge')?.remove();
            const btn = card.querySelector('.room-book-btn');
            if (!btn) return;
            btn.disabled       = false;
            btn.style.cssText  = '';
            card.style.opacity = '';
            if (!btn.classList.contains('is-selected')) btn.textContent = 'Pilih Kamar';
        });
    }

    async function checkAvailability(checkin, checkout) {
        if (!checkin || !checkout) { resetRoomCards(); return; }
        if (checkin === lastCheckin && checkout === lastCheckout) return;
        lastCheckin  = checkin;
        lastCheckout = checkout;

        try {
            const url = `/teman_singgah/user/pages/check_room_availability.php`
                      + `?listing_id=${BASE_LISTING_ID}`
                      + `&checkin=${encodeURIComponent(checkin)}`
                      + `&checkout=${encodeURIComponent(checkout)}`;
            const data = await fetch(url).then(r => r.json());
            if (data.success) applyAvailability(data.availability);
        } catch (e) {
            console.error('Cek ketersediaan gagal:', e);
        }
    }

    function watchDateInputs() {
        const checkinEl  = document.getElementById('checkinInput');
        const checkoutEl = document.getElementById('checkoutInput');
        if (!checkinEl || !checkoutEl) return;

        new MutationObserver(() => {
            const ci = checkinEl.dataset.value  || '';
            const co = checkoutEl.dataset.value || '';
            if (ci && co) {
                checkAvailability(ci, co);
            } else {
                lastCheckin = lastCheckout = null;
                resetRoomCards();
            }
        }).observe(checkinEl,  { attributes: true, attributeFilter: ['data-value'] });

        new MutationObserver(() => {
            const ci = checkinEl.dataset.value  || '';
            const co = checkoutEl.dataset.value || '';
            if (ci && co) {
                checkAvailability(ci, co);
            } else {
                lastCheckin = lastCheckout = null;
                resetRoomCards();
            }
        }).observe(checkoutEl, { attributes: true, attributeFilter: ['data-value'] });
    }

    const style = document.createElement('style');
    style.textContent = `
        .room-avail-badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 10px;
        }
        .room-avail-badge.penuh        { background:#fee2e2; color:#dc2626; }
        .room-avail-badge.sisa-sedikit { background:#fef3c7; color:#d97706; }
        .room-avail-badge.tersedia     { background:#d1fae5; color:#059669; }
        .room-book-btn[disabled]       { pointer-events: none; }
    `;
    document.head.appendChild(style);

    document.addEventListener('DOMContentLoaded', watchDateInputs);
})();