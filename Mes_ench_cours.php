<?php


include 'Connexion.php';

session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);

if (isset($_SESSION['Num_client'])) {
  $num_client = $_SESSION['Num_client'];

  // Requête pour récupérer les informations de l'utilisateur
  $sql = "SELECT oeuvre.titre, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, utilisateur.Nom, utilisateur.Prenom
          FROM oeuvre
          JOIN utilisateur ON oeuvre.Num_client_a = utilisateur.Num_client 
          WHERE oeuvre.Num_client_LastO = ?
          AND oeuvre.Num_client_a != 0";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $num_client);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $oeuvres = $result->fetch_all(MYSQLI_ASSOC);
  } else {
      $oeuvres = [];
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
    <link rel="stylesheet" href="Mes_ventes.css" />
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
  </head>
  <body>
    <script src="index.js"></script>
    <?php include ('barre de recherche.php'); ?>
    <main>
    <div class="historique-container">
        <h1>Mes enchères en cours</h1>
        <php>
        <?php if ($oeuvres): ?>
        <table>
            <tr>
                <th>Titre</th>
                <th>Prix</th>
                <th>Date d'obtention</th>
                <th>Vendeur</th>
            </tr>
            <?php foreach ($oeuvres as $oeuvre ): ?>
                <tr>
                    <td><?php echo htmlspecialchars($oeuvre['titre']); ?></td>
                    <td><?php echo htmlspecialchars($oeuvre['Prix_Loffre']); ?>€</td>
                    <td><?php $dateLoffre = new DateTime($oeuvre['Date_Loffre']); // Crée un objet DateTime à partir de la date
                                $dateLoffre->modify('+1 day'); // Ajoute 24 heures
                                echo htmlspecialchars($dateLoffre->format('Y-m-d H:i:s')); // Affiche la date dans le format souhaité
                                ?></td>
                    <td><?php echo htmlspecialchars($oeuvre['Prenom']); ?>  <?php echo htmlspecialchars($oeuvre['Nom']); ?></td>
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