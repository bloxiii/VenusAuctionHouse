<?php


include 'Connexion.php';

session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);

if (isset($_SESSION['Num_client'])) {
  $num_client = $_SESSION['Num_client'];

  // Requête pour récupérer les informations de l'utilisateur
  $sql = "SELECT oeuvre.titre, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, utilisateur.Nom 
        FROM oeuvre
        JOIN utilisateur ON oeuvre.Num_client_v = utilisateur.Num_client
        WHERE oeuvre.Num_client_a = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $num_client);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $oeuvres = $result->fetch_all(MYSQLI_ASSOC);
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
    <link rel="stylesheet" href="Mes_achats.css" />
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
            <a href="SeConnecter.php">Connexion</a>
            <a href="Inscription.php">Inscription</a>
          </li>
        <li><a href="des_oeuvres.php">Oeuvre à vendre</a></li>
        <li><a href="#">FAQ</a></li>
        </ul>
    <?php endif; ?>
      </nav>
    </header>
    <main>
    <div class="historique-container">
        <h1>Historique des achats</h1>
        <php>
        <?php if ($oeuvres): ?>
        <table>
            <tr>
                <th>Titre</th>
                <th>Vendeur</th>
                <th>Date</th>
                <th>Prix</th>
            </tr>
            <?php foreach ($oeuvres as $oeuvre ): ?>
                <tr>
                    <td><?php echo htmlspecialchars($oeuvre['titre']); ?></td>
                    <td><?php echo htmlspecialchars($oeuvre['Nom']); ?></td>
                    <td><?php echo htmlspecialchars($oeuvre['Date_Loffre']); ?></td>
                    <td><?php echo htmlspecialchars($oeuvre['Prix_Loffre']); ?>€</td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Aucune œuvre trouvée pour cet utilisateur.</p>
    <?php endif; ?>
    </div>
    </main>
    <script src="burger-menu.js"></script>
  </body>
</html>