<?php



$servername = "127.0.0.1"; // Adresse du serveur MySQL (local)
$username = "Enzo";        // Nom d'utilisateur MySQL
$password = "Basededonneepsw";            // Mot de passe MySQL (laisser vide si pas de mot de passe par défaut)
$dbname = "venus"; // Nom de la base de données

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Échec de la connexion: " . $conn->connect_error);
}
echo "BDD Connecté";
?>

