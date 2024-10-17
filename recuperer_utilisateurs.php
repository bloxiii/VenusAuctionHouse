<?php
include 'connexion.php'; // Inclut le fichier de connexion à la base de données

$sql = "SELECT Nom, email FROM utilisateur"; // Requête SQL pour sélectionner les colonnes Nom et email
$result = $conn->query($sql); // Exécute la requête SQL

if ($result->num_rows > 0) { // Vérifie s'il y a des résultats
    // Sortie des résultats
    while($row = $result->fetch_assoc()) { // Parcourt chaque ligne de résultat
        echo "Nom: " . $row["Nom"]. " - Email: " . $row["email"]. "<br>"; // Affiche le nom et l'email
    }
} else {
    echo "0 résultats"; // Affiche un message si aucun résultat n'est trouvé
}
$conn->close(); // Ferme la connexion à la base de données
?>