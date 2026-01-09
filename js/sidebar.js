const hamburger = document.querySelector(".hamburger");
const navLinks = document.querySelector(".nav-links");

if(hamburger && navLinks) {
    hamburger.addEventListener("click", () => {
        hamburger.classList.toggle("active");
        navLinks.classList.toggle("active");
    });

    document.querySelectorAll(".nav-links a").forEach(n => n.addEventListener("click", () => {
        hamburger.classList.remove("active");
        navLinks.classList.remove("active");
    }));
}