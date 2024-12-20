<?php


// Connexion à la base de données
include ("Connexion.php");

session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);


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

        include('PopUp/EmailSend.php');
    } else {
        echo "Aucun utilisateur trouvé avec cet e-mail.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Venus Auction House</title>
    <link rel="stylesheet" href="connexion.css" />
  </head>
<body>
<?php include ('barre de recherche.php'); ?>
    <div class="form-container">
    <form method="POST" class='mform'>
        <label for="email">Mot de passe oublié
        </label>
        <input type="email" name="email" placeholder="Entrez votre email" required>
        <button type="submit" class="custom-button">Envoyer</button>
    </form>
</div>
</body>
<script src="burger-menu.js"></script>
</html>
