// Function to toggle the navigation menu
function toggleMenu() {
    const navMenu = document.getElementById("navMenu");
    navMenu.classList.toggle("active");
}

// Function to toggle dropdown menus
function toggleDropdown() {
    const dropdowns = document.querySelectorAll('.dropdown-content');
    dropdowns.forEach(menu => menu.classList.toggle('active'));
}

// Add event listeners to trigger the existing functions
document.addEventListener("DOMContentLoaded", function () {
    // Attach toggleMenu to the menu toggle button
    const menuToggle = document.getElementById("menuToggle");
    menuToggle.addEventListener("click", toggleMenu);

    // Attach toggleDropdown to the user photo or dropdown trigger
    const userPhoto = document.getElementById("userPhoto");
    userPhoto.addEventListener("click", toggleDropdown);
});