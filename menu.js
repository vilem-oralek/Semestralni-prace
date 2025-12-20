function toggleMenu() {
    const navMenu = document.getElementById("navMenu");
    navMenu.classList.toggle("active");
  }
function toggleDropdown() {
    const dropdowns = document.querySelectorAll('.dropdown-content');
    dropdowns.forEach(menu => menu.classList.toggle('active'));
}