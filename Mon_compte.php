<?php



include 'test_email.php';
include 'Connexion.php';


// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);


if (isset($_SESSION['Num_client'])) {
    $num_client = $_SESSION['Num_client'];

    // Requête pour récupérer les informations de l'utilisateur
    $sql = "SELECT * FROM utilisateur WHERE Num_client = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $num_client);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc(); // Récupère les données de l'utilisateur
    } else {
        echo "Utilisateur non trouvé.";
        exit;
    }
} else {
    echo "Vous n'êtes pas connecté.";
    exit;
}

?>
<!DOCTYPE html>

<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Venus Auction House</title>
    <link rel="stylesheet" href="Mon_compte.css?v=1.1" />
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
  </head>
  <body>
    <script src="index.js"></script>
    <?php include ('barre de recherche.php'); ?>
    <div class="user-info-box">
      <form>
        <label for="name">Nom:</label>
        <?php echo htmlspecialchars($user['Nom']); ?>

        <label for="surname">Prénom:</label>
        <?php echo htmlspecialchars($user['Prenom']); ?>


        <label for="address">Addresse:</label>
        <?php echo htmlspecialchars($user['Adresse']); ?>

        <label for="email">Email:</label>
        <?php echo htmlspecialchars($user['email']); ?>

        <label for="password">Mot de passe:</label>
        <?php echo htmlspecialchars($user['Mot_de_passe']); ?>

      </form>
    </div>
    <script src="burger-menu.js"></script>
  </body>
</html>