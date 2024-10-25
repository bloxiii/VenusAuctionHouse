<?php
session_start(); // Démarre la session

// Détruit toutes les variables de session
$_SESSION = [];

// Détruire la session
session_destroy();

// Rediriger l'utilisateur vers la page de connexion ou la page d'accueil
header("Location: SeConnecter.php");
exit();
?>

