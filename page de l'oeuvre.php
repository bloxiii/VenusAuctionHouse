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
    <link rel="stylesheet" href="page de l'oeuvre.css" />
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
        <div class="burger-menu" id="burger-menu">
          <!-- Icone du burger (simple bouton pour le moment) -->
          <button id="burger-btn">☰</button>
        </div>
        <!-- Menu caché initialement -->
        <?php if ($is_logged_in): ?>
        <ul id="menu" style="display: none">
          <li><a href="Mon_compte.php">Mon compte</a></li>
          <li>
            <a href="#">Oeuvre à vendre</a>
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
            <a href="SeConnecter.php">Connexion</a>
            <a href="Inscription.php">Inscription</a>
          </li>
        <li><a href="#">Oeuvre à vendre</a></li>
        <li><a href="#">FAQ</a></li>
        </ul>
    <?php endif; ?>
        
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
