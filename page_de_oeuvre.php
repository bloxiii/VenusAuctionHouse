<?php

include 'Connexion.php';
session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);

if (isset($_GET['Num_oeuvre'])) {
  $num_oeuvre = $_GET['Num_oeuvre'];

  // Requête pour récupérer les informations de l'œuvre
  $sql = "SELECT * FROM oeuvre WHERE Num_oeuvre = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $num_oeuvre);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $oeuvre = $result->fetch_assoc(); // Récupère les données de l'œuvre
  }

}
?>

<html lang="fr">

  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Venus Auction House</title>
    <link rel="stylesheet" href="page_de_oeuvre.css" />
  </head>
  <body>
    <header>
      <div class="header-container">
        <img src="Logo.png" alt="Venus Auction House Logo" class="logo" />
        <input
          type="search"
          placeholder="Barre de recherche"
          class="search-bar"
        />
      </div>
      <nav>
      <?php include 'Navigation/nav.php' ?>
      </nav>
    </header>
    <main class="centre">
      <div class="container">
        <div class="gestion">
          <div class="image-section">
            <img src="arbre.png" alt="L'Arbre Rouge" />
          </div>
          <div class="description-section">
            <h2><?php echo htmlspecialchars($oeuvre['titre']); ?></h2>
            <p>
            <?php echo htmlspecialchars($oeuvre['Description']); ?>
            </p>
            <p class="details">
              Date de l’œuvre : 1942 <br />
              Auteur : Salvator Dali
            </p>
          </div>
        </div>
        <div class="offer-section">
          <h3>Offre</h3>
          <p><strong>Temps restant :</strong> 10 heures</p>
          <p><strong>Dernière offre :</strong> 150.000 €</p>
          <button class="Surenchérir">Surenchérir pour 157.500 €</button>
        </div>
      </div>
    </main>
    <script src="burger-menu.js"></script>
  </body>
</html>
