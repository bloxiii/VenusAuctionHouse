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
    <link rel="stylesheet" href="Mon_compte.css" />
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
  </head>
  <body>
    <script src="index.js"></script>
    <header class="header">
      <div class="header-container">
        <img src="logo.png" alt="Venus Auction House Logo" class="logo" />
        <input
          type="search"
          placeholder="Barre de recherche"
          class="search-bar"
        />
      </div>
      <nav>
        <div class="burger-menu" id="burger-menu">
          <!-- Icone du burger (simple bouton pour le moment) -->
          <button id="burger-btn">☰</button>
        </div>
        <!-- Menu caché initialement -->
        <?php if ($is_logged_in): ?>
        <ul id="menu" style="display: none">
          <li><a href="Mon_compte.php">Mon compte</a></li>
          <li>
            <a href="des_oeuvres.php">Oeuvre à vendre</a>
            <a href="#">Mes enchères en cours</a>
            <a href="Mes_achats.php">Mes achats</a>
          </li>
          <li>
            <a href="#">Mes annonces</a>
            <a href="Mes_ventes.php">Mes ventes</a>
          </li>
          <li><a href="#">FAQ</a></li>
          <li><a href="Logout.php">Se déconnecter</a></li>
          </ul>
          <?php else: ?>
        <!-- Menu pour les utilisateurs invités -->
        <ul id="menu" style="display: none">
          <li>
            <a href="Connexion.php">Connexion</a>
            <a href="Inscription.php">Inscription</a>
          </li>
        <li><a href="des_oeuvres.php">Oeuvre à vendre</a></li>
        <li><a href="#">FAQ</a></li>
        </ul>
    <?php endif; ?>
      </nav>
    </header>
    <div class="user-info-box">
      <form>
        <label for="name">Nom:</label>
        <?php echo htmlspecialchars($user['Nom']); ?>

        <label for="surname">Prénom:</label>
        <?php echo htmlspecialchars($user['Prenom']); ?>

        <label for="birthdate">Date de naissance:</label>
        <input type="date" id="birthdate" name="birthdate" required />

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