<?php

session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);

// Inclure la connexion à la base de données
include 'connexion.php';


if (isset($_POST['delete_oeuvre'])) {
    $oeuvre_id = $_POST['oeuvre_id'];

    // Supprimer l'œuvre avec l'ID correspondant
    $stmt = $conn->prepare("DELETE FROM oeuvre WHERE Num_oeuvre = ?");
    $stmt->bind_param("i", $oeuvre_id);
    if ($stmt->execute()) {
        echo "Œuvre supprimée avec succès.";
    } else {
        echo "Erreur lors de la suppression de l'œuvre.";
    }
}

// Récupérer toutes les œuvres
$stmt = $conn->query("SELECT Num_oeuvre, Titre FROM oeuvre");
$oeuvres = $stmt->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des œuvres</title>
</head>
<body>
    <h1>Liste des œuvres</h1>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($oeuvres as $oeuvre): ?>
                <tr>
                    <td><?= htmlspecialchars($oeuvre['Num_oeuvre']) ?></td>
                    <td><?= htmlspecialchars($oeuvre['Titre']) ?></td>
                    <td>
                        <!-- Bouton "Supprimer" -->
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="oeuvre_id" value="<?= htmlspecialchars($oeuvre['Num_oeuvre']) ?>">
                            <button type="submit" name="delete_oeuvre" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette œuvre ?');">
                                Supprimer
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
