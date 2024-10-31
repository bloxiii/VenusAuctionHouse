<?php



include 'test_email.php';
include 'Connexion.php';


// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);

// Supposons que vous obtenez le style depuis une requête GET ou une autre source.
$style = isset($_GET['style']) ? $_GET['style'] : '*';

$sql = "SELECT oeuvre.titre, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom
        FROM oeuvre
        JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
        WHERE (oeuvre.Style = ? OR ? = '*')";

// Préparer la requête
$stmt = $conn->prepare($sql);

$stmt->bind_param("ss", $style, $style);

$stmt->execute();

$result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $oeuvres = $result->fetch_all(MYSQLI_ASSOC);
  } else {
      echo "<p>Pas d'œuvre pour le style : " . htmlspecialchars($style) . "</p>" ;
      exit;
  }

  $query = "SELECT Style FROM oeuvre"; // Remplace 'styles' par le nom de votre table
  $stmt = $conn->prepare($query);
  $stmt->execute();
  $result = $stmt->get_result();
  $styles = $result->fetch_all(MYSQLI_ASSOC);


?>
<!DOCTYPE html>

<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Venus Auction House</title>
    <link rel="stylesheet" href="des_oeuvres.css" />
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
            <a href="Connexion.php">Connexion</a>
            <a href="Inscription.php">Inscription</a>
          </li>
        <li><a href="#">Oeuvre à vendre</a></li>
        <li><a href="#">FAQ</a></li>
        </ul>
    <?php endif; ?>
      </nav>
    </header>
    <div class="header2">
        <select>
            <option value="">Auteur</option>
            <option value="Salvador Dali">Salvador Dali</option>
            <option value="Picasso">Picasso</option>
            <!-- Ajouter d'autres auteurs -->
        </select>
        <select>
            <option value="">Prix</option>
            <option value="asc">Croissant</option>
            <option value="desc">Décroissant</option>
        </select>
        <select>
            <option value="">Siècle</option>
            <option value="20e">20e siècle</option>
            <option value="21e">21e siècle</option>
        </select>
        <select id="style-select" onchange="filterByStyle()">
    <option value="">Style</option>
    <?php foreach ($styles as $style): ?>
        <option value="<?= htmlspecialchars($style['Style']) ?>"><?= htmlspecialchars($style['Style']) ?></option>
    <?php endforeach; ?>
</select>
    </div>

    <!-- Conteneur pour les cartes d'œuvres -->
    <div class="gallery">
        <?php foreach ($oeuvres as $oeuvre): ?>
            <div class="card">
                <div class="card-content">
                    <h3><?= htmlspecialchars($oeuvre['titre']) ?></h3>
                    <p>Date de l'œuvre : <?= htmlspecialchars($oeuvre['Date_oeuvre']) ?></p>
                    <p>Auteur : <?= htmlspecialchars($oeuvre['Prenom']) ?></p>
                    <p>Dernière offre : <?= htmlspecialchars($oeuvre['Prix_Loffre']) ?></p>
                    <p>Montant de l'offre : <?= number_format(htmlspecialchars($oeuvre['Prix_Loffre']), 0, ',', ' ') ?> €</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <script src="burger-menu.js"></script>
    <script src="Filter_style.js"></script>
  </body>
</html>