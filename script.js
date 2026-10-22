
document.addEventListener("DOMContentLoaded", () => {
    const menuButton = document.querySelector("#menu-toggle");
    const navLinks = document.querySelector("#nav-links");
    const header = document.querySelector(".header");

    // Vérifier que les éléments existent
    if (!menuButton || !navLinks) {
        console.error(
            "Menu introuvable : vérifie les IDs dans index.html."
        );
        return;
    }

    const links = navLinks.querySelectorAll('a[href^="#"]');

    // Ouvrir et fermer le menu
    function closeMenu() {
        navLinks.classList.remove("is-open");
        menuButton.setAttribute("aria-expanded", "false");
        menuButton.setAttribute("aria-label", "Ouvrir le menu");
    }

    menuButton.addEventListener("click", () => {
        const isOpen = navLinks.classList.toggle("is-open");

        menuButton.setAttribute("aria-expanded", String(isOpen));
        menuButton.setAttribute(
            "aria-label",
            isOpen ? "Fermer le menu" : "Ouvrir le menu"
        );
    });

    // Fermer le menu quand on sélectionne une destination
    links.forEach((link) => {
        link.addEventListener("click", () => {
            closeMenu();
        });
    });

    // Fermer en cliquant en dehors du menu
    document.addEventListener("click", (event) => {
        if (
            !navLinks.contains(event.target) &&
            !menuButton.contains(event.target)
        ) {
            closeMenu();
        }
    });

    // Fermer avec la touche Échap
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeMenu();
            menuButton.focus();
        }
    });

    // Fermer le menu si on repasse sur grand écran
    window.addEventListener("resize", () => {
        if (window.innerWidth > 768) {
            closeMenu();
        }
    });

    // Défilement fluide vers les sections
    links.forEach((link) => {
        link.addEventListener("click", (event) => {
            const targetId = link.getAttribute("href");
            const target = document.querySelector(targetId);

            if (target) {
                event.preventDefault();

                target.scrollIntoView({
                    behavior: window.matchMedia(
                        "(prefers-reduced-motion: reduce)"
                    ).matches ? "auto" : "smooth",
                    block: "start"
                });

                history.replaceState(null, "", targetId);
            }
        });
    });

    // Mettre en évidence le lien de la section visible
    const sections = document.querySelectorAll(
        "#accueil, #services, #apropos, #realisations, #contact"
    );

    if ("IntersectionObserver" in window) {
        const sectionObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        links.forEach((link) => {
                            const isCurrent =
                                link.getAttribute("href") ===
                                "#" + entry.target.id;

                            link.classList.toggle("active", isCurrent);

                            if (isCurrent) {
                                link.setAttribute("aria-current", "location");
                            } else {
                                link.removeAttribute("aria-current");
                            }
                        });
                    }
                });
            },
            {
                rootMargin: "-20% 0px -65% 0px"
            }
        );

        sections.forEach((section) => {
            sectionObserver.observe(section);
        });
    }

    console.log("Pixel Impact : navigation initialisée.");
});
