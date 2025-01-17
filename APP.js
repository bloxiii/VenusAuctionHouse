// JavaScript source code
document.getElementById("contactButton").addEventListener("click", function () {
  document.getElementById("contactPopup").style.display = "flex";
});

document.getElementById("closePopup").addEventListener("click", function () {
  document.getElementById("contactPopup").style.display = "none";
});

window.addEventListener("click", function (event) {
  const popup = document.getElementById("contactPopup");
  if (event.target === popup) {
    popup.style.display = "none";
  }
});

document.addEventListener("DOMContentLoaded", function () {
  AOS.init({
    duration: 1000, // Durée de l'animation (en ms)
    easing: "ease-out-quart", // Type d'animation
    once: true, // Lancer l'animation une seule fois
  });
});
