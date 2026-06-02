/**
 * admin/scripts/payout_actions.js
 * Handle tombol aksi di payouts.php
 */
document.addEventListener('DOMContentLoaded', () => {

  document.querySelectorAll('.btn-proses').forEach(btn => {
    btn.addEventListener('click', () => {
      if (!confirm('Tandai payout ini sebagai sedang diproses?')) return;
      doAction('proses', btn.dataset.id, btn);
    });
  });

  document.querySelectorAll('.btn-retry').forEach(btn => {
    btn.addEventListener('click', () => {
      if (!confirm('Ulangi pencairan dana ini?')) return;
      doAction('retry', btn.dataset.id, btn);
    });
  });

  document.querySelectorAll('.btn-cancel').forEach(btn => {
    btn.addEventListener('click', () => {
      const reason = prompt('Alasan pembatalan:', 'Dibatalkan oleh admin');
      if (reason === null) return;
      doAction('cancel', btn.dataset.id, btn, { reason });
    });
  });

  async function doAction(action, payoutId, btn, extra = {}) {
    btn.disabled = true;
    const body = new URLSearchParams({ action, payout_id: payoutId, ...extra });
    try {
      const res  = await fetch(window.location.pathname, { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        location.reload();
      } else {
        alert(data.message || 'Terjadi kesalahan.');
        btn.disabled = false;
      }
    } catch {
      alert('Gagal menghubungi server.');
      btn.disabled = false;
    }
  }

});