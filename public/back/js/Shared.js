// ═══════════════════════════════════════════════
//  AUTICARE — Shared JS (Symfony Version)
//  ONLY interactions (NO HTML generation)
// ═══════════════════════════════════════════════

(function () {

  // ─────────────────────────────────────────────
  // Toggle Sidebar Collapse
  // ─────────────────────────────────────────────
  window.toggleSidebar = function () {
    const sidebar = document.getElementById('sidebar');
    const navbar  = document.getElementById('navbar');
    const main    = document.getElementById('main-content');

    if (!sidebar) return;

    const collapsed = sidebar.classList.toggle('collapsed');

    if (navbar) navbar.classList.toggle('full-width', collapsed);
    if (main)   main.classList.toggle('full-width', collapsed);
  };

  // ─────────────────────────────────────────────
  // Toggle Sections (accordion menu)
  // ─────────────────────────────────────────────
  window.toggleSection = function (id) {
    const wrap = document.getElementById('wrap-' + id);
    if (!wrap) return;

    const isOpen = wrap.classList.contains('open');

    document.querySelectorAll('.nav-item-wrap.open')
      .forEach(w => w.classList.remove('open'));

    if (!isOpen) wrap.classList.add('open');
  };

  // ─────────────────────────────────────────────
  // Active link highlight (auto)
  // ─────────────────────────────────────────────
  document.addEventListener("DOMContentLoaded", () => {
    const links = document.querySelectorAll(".nav-sub-link, .nav-link-item");

    links.forEach(link => {
      if (link.href === window.location.href) {
        link.classList.add("active");

        // open parent section automatically
        const parent = link.closest(".nav-item-wrap");
        if (parent) parent.classList.add("open");
      }
    });
  });

})();