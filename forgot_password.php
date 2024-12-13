<?php


// Connexion à la base de données
include ("Connexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Vérifier si l'utilisateur existe
    $stmt = $conn->prepare("SELECT Num_client FROM utilisateur WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Générer un token unique
        $token = bin2hex(random_bytes(50));

        // Insérer le token et l'heure d'expiration
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expiration dans 1 heure
        $stmt = $conn->prepare("UPDATE utilisateur SET reset_token = ?, token_expiry = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expiry, $email);
        $stmt->execute();

        // Envoyer l'e-mail
        $resetLink = "http://localhost/venusauctionhouse/reset_password.php?token=" . $token;
        $subject = "Réinitialisation de votre mot de passe";
        $message = "Cliquez sur ce lien pour réinitialiser votre mot de passe : $resetLink";
        $headers = "From: noreply@example.com";

        mail($email, $subject, $message, $headers);

        echo "Un e-mail de réinitialisation a été envoyé.";
    } else {
        echo "Aucun utilisateur trouvé avec cet e-mail.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mot de passe oublié</title>
</head>
<body>
    <form method="POST">
        <label for="email">Adresse e-mail :</label>
        <input type="email" name="email" required>
        <button type="submit">Envoyer</button>
    </form>
</body>
</html>
