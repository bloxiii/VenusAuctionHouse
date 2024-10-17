const burgerBtn = document.getElementById("burger-btn");
const menu = document.getElementById("menu");

// Ajout de l'événement pour le bouton burger
burgerBtn.addEventListener("click", function () {
  if (menu.style.display === "none") {
    menu.style.display = "block";
  } else {
    menu.style.display = "none";
  }
});