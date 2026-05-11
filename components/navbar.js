const navLinks = document.querySelectorAll(".nav-link");
const navIndicator = document.querySelector(".nav-indicator");

function moveNavIndicator(link) {
  if (!navIndicator) return;

  const navContainer = navIndicator.parentElement;
  const containerRect = navContainer.getBoundingClientRect();
  const linkRect = link.getBoundingClientRect();

  navIndicator.style.left = `${linkRect.left - containerRect.left}px`;
  navIndicator.style.width = `${linkRect.width}px`;
}

navLinks.forEach((link) => {
  link.addEventListener("click", () => {
    navLinks.forEach((l) => l.classList.remove("active"));
    link.classList.add("active");
    moveNavIndicator(link);
  });
});

const activeNavLink = document.querySelector(".nav-link.active");
if (activeNavLink) requestAnimationFrame(() => moveNavIndicator(activeNavLink));

window.addEventListener("resize", () => {
  const active = document.querySelector(".nav-link.active");
  if (active) moveNavIndicator(active);
});