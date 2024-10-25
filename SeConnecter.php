<?php
session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);
?>


<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Venus Auction House</title>
    <link rel="stylesheet" href="connexion.css" />
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
            <a href="#">Mes achats</a>
          </li>
          <li>
            <a href="#">Mes annonces</a>
            <a href="#">Mes ventes</a>
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
    <main>
      <div class="form-container">
        <form action="test_email.php" method="post">
          <label for="email"></label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="Entrez votre email"
            required
          />

          <label for="password"></label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Entrez votre mot de passe"
            required
          />

          <button type="submit" class="custom-button">Se connecter</button>
          <a href="#" class="forgot-password">Mot de passe oublié</a>
          <a href="Inscription.php" class="forgot-password">Créer un compte</a>
        </form>
      </div>
    </main>
    <script src="burger-menu.js"></script>
  </body>
</html>
