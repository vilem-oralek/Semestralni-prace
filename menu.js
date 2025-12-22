document.addEventListener('click', function(e) {

    // --- 1. Mobilní menu (Hamburger) ---
    // Hledáme, zda kliknutí bylo na element #menuToggle nebo uvnitř něj
    const menuToggle = e.target.closest('#menuToggle');
    if (menuToggle) {
        const navMenu = document.getElementById("navMenu");
        if (navMenu) {
            navMenu.classList.toggle("active");
        }
    }

    // --- 2. Profilové menu (Dropdown) ---
    // Hledáme, zda kliknutí bylo na #userPhoto
    const userPhoto = e.target.closest('#userPhoto');
    if (userPhoto) {
        // Najdeme všechny dropdowny a přepneme jim třídu active
        const dropdowns = document.querySelectorAll('.dropdown-content');
        dropdowns.forEach(menu => menu.classList.toggle('active'));

        // Zabráníme, aby se dropdown hned zase zavřel (viz níže)
        e.stopPropagation();
    }

    // --- 3. Zavření dropdownu při kliknutí jinam ---
    // Pokud kliknutí nebylo uvnitř user-controls, zavřeme dropdowny
    if (!e.target.closest('.user-controls')) {
        const dropdowns = document.querySelectorAll('.dropdown-content');
        dropdowns.forEach(menu => menu.classList.remove('active'));
    }
});