<?php

include 'Connexion.php';

// Vérifie si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupère la valeur de l'option sélectionnée
    $selectedStyle = isset($_POST['style']) ? $_POST['style'] : null;

    // Affiche ou utilise la valeur récupérée
    if ($selectedStyle) {
        echo "Le style sélectionné est : " . htmlspecialchars($selectedStyle);
    } else {
        echo "Aucun style sélectionné.";
    }
}
else {
    echo "rien" ;
}
?>