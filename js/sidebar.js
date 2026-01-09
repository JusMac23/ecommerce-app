const adminHamburger = document.querySelector(".admin-hamburger");
const indexHamburger = document.querySelector(".index-hamburger");
const navLinks = document.querySelector(".nav-links");

if(adminHamburger && navLinks) {
    adminHamburger.addEventListener("click", () => {
        adminHamburger.classList.toggle("active");
        navLinks.classList.toggle("active");
    });

    document.querySelectorAll(".nav-links a").forEach(n => n.addEventListener("click", () => {
        adminHamburger.classList.remove("active");
        navLinks.classList.remove("active");
    }));
}
else if(indexHamburger && navLinks) {
    indexHamburger.addEventListener("click", () => {
        indexHamburger.classList.toggle("active");
        navLinks.classList.toggle("active");
    });

    document.querySelectorAll(".nav-links a").forEach(n => n.addEventListener("click", () => {
        indexHamburger.classList.remove("active");
        navLinks.classList.remove("active");
    }));
}