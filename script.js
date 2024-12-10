function toggleFAQ(sectionID) {
  const content = document.getElementById(sectionID);
  const isVisible = content.style.display === "block";

  // Cache toutes les sections
  document.querySelectorAll(".faq-content").forEach(section => {
      section.style.display = "none";
  });

  // Affiche la section sélectionnée si elle est cachée
  if (!isVisible) {
      content.style.display = "block";
  }
}
