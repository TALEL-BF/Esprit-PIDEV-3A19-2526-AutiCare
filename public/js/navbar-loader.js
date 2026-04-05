/**
 * navbar-loader.js
 * Charge automatiquement la navbar depuis navbar.html
 * et marque le lien actif selon la page courante.
 */
document.addEventListener("DOMContentLoaded", function () {
    const placeholder = document.getElementById("navbar-placeholder");
    if (!placeholder) return;

    fetch("navbar.html")
        .then(function (response) {
            if (!response.ok) throw new Error("Impossible de charger navbar.html");
            return response.text();
        })
        .then(function (html) {
            placeholder.innerHTML = html;

            // Déterminer la page courante
            const currentFile = window.location.pathname.split("/").pop() || "index.html";
            const currentPage = currentFile.replace(".html", "");

            // Marquer le lien actif
            const navLinks = placeholder.querySelectorAll(".nav-link[data-page]");
            navLinks.forEach(function (link) {
                link.classList.remove("active");
                if (link.getAttribute("data-page") === currentPage) {
                    link.classList.add("active");
                }
            });
        })
        .catch(function (error) {
            console.error("Erreur navbar :", error);
        });
});
