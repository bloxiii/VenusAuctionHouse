<?php

session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);

// Inclure la connexion à la base de données
include 'connexion.php';


if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    // Supprimer l'utilisateur avec l'ID correspondant
    $stmt = $conn->prepare("DELETE FROM utilisateur WHERE Num_client = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        echo "Utilisateur supprimé avec succès.";
    } else {
        echo "Erreur lors de la suppression de l'utilisateur.";
    }
}

// Récupérer tous les utilisateurs
$stmt = $conn->query("SELECT Num_client, Nom, Prenom, email FROM utilisateur");
$users = $stmt->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs</title>
</head>
<body>
    <h1>Liste des utilisateurs</h1>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['Num_client']) ?></td>
                    <td><?= htmlspecialchars($user['Nom']) ?></td>
                    <td><?= htmlspecialchars($user['Prenom']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <!-- Bouton "Supprimer" -->
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['Num_client']) ?>">
                            <button type="submit" name="delete_user" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
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