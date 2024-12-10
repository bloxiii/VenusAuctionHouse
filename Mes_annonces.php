<?php
include 'Connexion.php';

session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);

if (isset($_SESSION['Num_client'])) {
  $num_client = $_SESSION['Num_client'];

  // Requête pour récupérer les informations de l'utilisateur
  $sql = "SELECT oeuvre.titre, auteur.Prenom , auteur.Nom  , oeuvre.Prix_Loffre
        FROM oeuvre
        JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
        WHERE oeuvre.Num_client_v = ?";
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
    <link rel="stylesheet" href="Mes_annonces.css" />
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
  </head>
  <body>
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
      <?php include 'Navigation/nav.php' ?>
      </nav>
    </header>
  <body>
    <div class="container">
      <h1>Mes annonces</h1>
      <a href="Create_annonce.php" class="create-annonce">Créer une annonce</a>
      <?php if ($oeuvres): ?>
        <table>
            <tr>
                <th>Titre</th>
                <th>auteur</th>
                <th>Prix</th>
            </tr>
            <?php foreach ($oeuvres as $oeuvre ): ?>
                <tr>
                    <td><?php echo htmlspecialchars($oeuvre['titre']); ?></td>
                    <td><?php echo htmlspecialchars($oeuvre['Nom']); ?></td>
                    <td><?php echo htmlspecialchars($oeuvre['Prix_Loffre']); ?>€</td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <p>Aucune œuvre trouvée pour cet utilisateur.</p>
    <?php endif; ?>
    </div>
    <script src="burger-menu.js"></script>
  </body>
</html>
