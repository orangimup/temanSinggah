(function () {
  "use strict";

  const overlay     = document.getElementById("reviewPopupOverlay");
  const openBtn     = document.getElementById("openReviewPopup");
  const closeBtn    = document.getElementById("closeReviewPopup");
  const cancelBtn   = document.getElementById("cancelReviewBtn");
  const form        = document.getElementById("reviewForm");
  const textarea    = document.getElementById("reviewComment");
  const charCount   = document.getElementById("charCount");
  const ratingInput = document.getElementById("ratingInput");
  const starSelector  = document.getElementById("starSelector");
  const starLabelText = document.getElementById("starLabelText");
  const errorBox    = document.getElementById("reviewError");
  const successBox  = document.getElementById("reviewSuccess");
  const submitBtn   = document.getElementById("submitReviewBtn");

  if (!overlay || !openBtn) return;

  const STAR_LABELS = ["", "Jelek", "Kurang memuaskan", "Lumayan", "Bagus", "Luar biasa!"];
  let selectedRating = 0;

  function openPopup() {
    overlay.classList.add("open");
    overlay.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
    setTimeout(() => textarea.focus(), 250);
  }

  function closePopup() {
    overlay.classList.remove("open");
    overlay.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  }

  openBtn.addEventListener("click", openPopup);
  closeBtn.addEventListener("click", closePopup);
  cancelBtn.addEventListener("click", closePopup);

  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) closePopup();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && overlay.classList.contains("open")) closePopup();
  });

  const starBtns = starSelector.querySelectorAll(".star-btn");

  function renderStars(hovered = 0, selected = 0) {
    const active = hovered || selected;
    starBtns.forEach((btn) => {
      const val  = parseInt(btn.dataset.value);
      const icon = btn.querySelector("i");
      if (val <= active) {
        icon.className = "ph-fill ph-star";
        btn.classList.add("selected");
      } else {
        icon.className = "ph-bold ph-star";
        btn.classList.remove("selected");
      }
    });
    starLabelText.textContent = STAR_LABELS[hovered] || STAR_LABELS[selected] || "Pilih rating";
  }

  starBtns.forEach((btn) => {
    const val = parseInt(btn.dataset.value);
    btn.addEventListener("mouseenter", () => renderStars(val, selectedRating));
    btn.addEventListener("mouseleave", () => renderStars(0, selectedRating));
    btn.addEventListener("click", () => {
      selectedRating    = val;
      ratingInput.value = val;
      renderStars(0, selectedRating);
    });
  });

  textarea.addEventListener("input", () => {
    charCount.textContent = textarea.value.length;
  });

  function showError(msg) {
    errorBox.textContent      = msg;
    errorBox.style.display    = "block";
    successBox.style.display  = "none";
  }

  function showSuccess(msg) {
    successBox.textContent   = msg;
    successBox.style.display = "block";
    errorBox.style.display   = "none";
  }

  function setLoading(on) {
    submitBtn.disabled = on;
    submitBtn.classList.toggle("loading", on);
    submitBtn.innerHTML = on
      ? '<span class="spinner"></span> Mengirim...'
      : '<i class="ph-bold ph-paper-plane-tilt"></i> Kirim Ulasan';
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    errorBox.style.display   = "none";
    successBox.style.display = "none";

    if (selectedRating === 0) {
      showError("Pilih rating bintang terlebih dahulu.");
      return;
    }
    if (textarea.value.trim().length < 10) {
      showError("Komentar terlalu pendek, minimal 10 karakter.");
      return;
    }

    setLoading(true);

    try {
      const res  = await fetch("/teman_singgah/user/pages/submit_review.php", {
        method: "POST",
        body: new FormData(form),
      });
      const data = await res.json();

      if (data.success) {
        showSuccess("Ulasan berhasil dikirim! Halaman akan dimuat ulang...");
        setTimeout(() => window.location.reload(), 1800);
      } else {
        showError(data.message || "Terjadi kesalahan, coba lagi.");
        setLoading(false);
      }
    } catch (err) {
      showError("Gagal terhubung ke server. Periksa koneksi internet Anda.");
      setLoading(false);
    }
  });
})();