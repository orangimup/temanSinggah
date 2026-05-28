document.addEventListener('DOMContentLoaded', () => {
  const cancelBtn = document.querySelector('.action-button-full.danger');
  if (cancelBtn) {
    cancelBtn.addEventListener('click', (e) => {
      const confirmed = confirm('Yakin ingin membatalkan pemesanan ini?');
      if (!confirmed) e.preventDefault();
    });
  }
});