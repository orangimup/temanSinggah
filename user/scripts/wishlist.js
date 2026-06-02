(function () {
  'use strict';

  const WISHLIST_URL = '/teman_singgah/user/pages/wishlist.php';
  const ICON_SAVED = '/teman_singgah/assets/icons/save_fill.svg';
  const ICON_UNSAVED = '/teman_singgah/assets/icons/save.svg';

  function extractIdFromHref(href) {
    if (!href) return null;
    const match = href.match(/[?&]id=(\d+)/);
    return match ? parseInt(match[1]) : null;
  }

  function getListingId(btn) {
    if (btn.dataset.listingId) return parseInt(btn.dataset.listingId);
    const card = btn.closest('a[href*="id="], [data-id]');
    if (!card) return null;
    return card.dataset.id
      ? parseInt(card.dataset.id)
      : extractIdFromHref(card.getAttribute('href'));
  }

  function setSaved(btn, saved) {
    if (btn.id === 'btnSave') {
      btn.classList.toggle('active', saved);
      btn.innerHTML = saved
        ? '<i class="ph-fill ph-heart"></i> Tersimpan'
        : '<i class="ph-bold ph-heart"></i> Simpan';
      return;
    }
    if (btn.tagName === 'BUTTON') {
      const img = btn.querySelector('img');
      if (img) img.src = saved ? ICON_SAVED : ICON_UNSAVED;
      btn.classList.toggle('active', saved);
    } else if (btn.tagName === 'IMG') {
      btn.src = saved ? ICON_SAVED : ICON_UNSAVED;
      btn.classList.toggle('active', saved);
    } else {
      const img = btn.querySelector('img');
      if (img) img.src = saved ? ICON_SAVED : ICON_UNSAVED;
      btn.classList.toggle('active', saved);
    }
  }

  function isSaved(btn) {
    return btn.classList.contains('active');
  }

  async function syncStatus() {
    try {
      const res = await fetch(WISHLIST_URL + '?action=status');
      const data = await res.json();
      if (data.status !== 'ok') return;

      const savedSet = new Set(data.saved_ids);

      document.querySelectorAll('.save-button').forEach(btn => {
        const id = getListingId(btn);
        if (id) setSaved(btn, savedSet.has(id));
      });

      document.querySelectorAll('.map-card-button.wishlist').forEach(btn => {
        const card = btn.closest('.map-property-card[data-marker-id]');
        if (!card) return;
        const id = parseInt(card.dataset.markerId);
        if (id) setSaved(btn, savedSet.has(id));
      });

      const btnSave = document.getElementById('btnSave');
      if (btnSave) {
        const id = parseInt(btnSave.dataset.listingId);
        if (id) setSaved(btnSave, savedSet.has(id));
      }
    } catch (_) { }
  }

  async function handleToggle(e, btn, listingId) {
    e.preventDefault();
    e.stopPropagation();

    if (localStorage.getItem('isLoggedIn') !== 'true') {
      const overlay = document.getElementById('authOverlay');
      if (overlay) {
        overlay.classList.add('active');
        document.querySelectorAll('.auth-step').forEach(s => s.classList.remove('active'));
        document.getElementById('authStepPilih')?.classList.add('active');
      }
      return;
    }

    const wasSaved = isSaved(btn);
    setSaved(btn, !wasSaved);

    try {
      const fd = new FormData();
      fd.append('action', 'toggle');
      fd.append('listing_id', listingId);
      const res = await fetch(WISHLIST_URL, { method: 'POST', body: fd });
      const data = await res.json();

      if (data.status === 'ok') {
        setSaved(btn, data.saved);
        syncSameListingButtons(listingId, data.saved);
      } else {
        setSaved(btn, wasSaved);
        if (data.message === 'login_required') {
          document.getElementById('authOverlay')?.classList.add('active');
        }
      }
    } catch (_) {
      setSaved(btn, wasSaved);
    }
  }

  function syncSameListingButtons(listingId, saved) {
    document.querySelectorAll('.save-button').forEach(btn => {
      if (getListingId(btn) === listingId) setSaved(btn, saved);
    });
    document.querySelectorAll('.map-card-button.wishlist').forEach(btn => {
      const card = btn.closest('.map-property-card[data-marker-id]');
      if (card && parseInt(card.dataset.markerId) === listingId) setSaved(btn, saved);
    });
    const btnSave = document.getElementById('btnSave');
    if (btnSave && parseInt(btnSave.dataset.listingId) === listingId) {
      setSaved(btnSave, saved);
    }
  }

  document.addEventListener('click', function (e) {
    const saveBtn = e.target.closest('.save-button');
    if (saveBtn) {
      e.preventDefault();
      e.stopPropagation();
      const id = getListingId(saveBtn);
      if (id) handleToggle(e, saveBtn, id);
      return;
    }

    const mapWishlist = e.target.closest('.map-card-button.wishlist');
    if (mapWishlist) {
      const card = mapWishlist.closest('.map-property-card[data-marker-id]');
      if (card) handleToggle(e, mapWishlist, parseInt(card.dataset.markerId));
      return;
    }

    const btnSave = e.target.closest('#btnSave');
    if (btnSave) {
      const id = parseInt(btnSave.dataset.listingId);
      if (id) handleToggle(e, btnSave, id);
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncStatus);
  } else {
    syncStatus();
  }

})();