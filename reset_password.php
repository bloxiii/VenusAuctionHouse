<?php
// Connexion à la base de données
include ('Connexion.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $token = $_GET['token'];

    // Vérifier le token et l'expiration
    $stmt = $conn->prepare("SELECT email FROM utilisateur WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $email = $result->fetch_assoc()['email'];
    } else {
        die("Lien invalide ou expiré.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'], $_POST['password'])) {
    $token = $_POST['token'];
    $newPassword = $_POST['password'];

    // Vérifier le token
    $stmt = $conn->prepare("SELECT email FROM utilisateur WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $email = $result->fetch_assoc()['email'];

        // Mettre à jour le mot de passe
        $stmt = $conn->prepare("UPDATE utilisateur SET Mot_de_passe = ?, reset_token = NULL, token_expiry = NULL WHERE email = ?");
        $stmt->bind_param("ss", $newPassword, $email);
        $stmt->execute();

        echo "Mot de passe réinitialisé avec succès.";
    } else {
        echo "Lien invalide ou expiré.";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Réinitialisation du mot de passe</title>
</head>
<body>
    <form method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <label for="password">Nouveau mot de passe :</label>
        <input type="password" name="password" required>
        <button type="submit">Réinitialiser</button>
    </form>
</body>
</html>
