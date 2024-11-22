<?php
include 'Connexion.php';

session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);


// Vérifie si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  // Récupère la valeur de l'option sélectionnée
  $selectedStyle = isset($_GET['style']) ? $_GET['style'] : null;
  $selectedPrice = isset($_GET['price']) ? $_GET['price'] : null;
  $selectedAuteur = isset($_GET['auteur']) ? $_GET['auteur'] : null;
  $selectedStiecle = isset($_GET['siecle']) ? $_GET['siecle'] : null;
}
else {
    echo "rien" ;
  }
//   // Affiche ou utilise la valeur récupérée


//  if ($selectedStyle) {
//       echo "Le style sélectionné est : " . htmlspecialchars($selectedStyle);
//   } else {
//       echo "Aucun style sélectionné.";
//   }
// }





// if ($selectedAuteur) {
//   echo "Le AUTEUR sélectionné est : " . htmlspecialchars($selectedAuteur);
// } else {
//   echo "Aucun AUTR sélectionné.";
// }








  // if ($selectedAuteur) {
  //   echo "Le AUTEUR sélectionné est : " . htmlspecialchars($selectedAuteur);
  // } else {
  //   echo "Aucun AUTR sélectionné.";
  // }



  if ($selectedAuteur && $selectedStyle && !$selectedStiecle && !$selectedPrice) {
    $sql = "SELECT oeuvre.titre, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom
            FROM oeuvre
            JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
            WHERE oeuvre.Style = ? AND auteur.Nom = ? ";
  
    // Préparer la requête
    $stmt = $conn->prepare($sql);
  
    $stmt->bind_param('ss' , $selectedStyle, $selectedAuteur);
  
    $stmt->execute();
    }

  
if ($selectedAuteur && !$selectedStyle && !$selectedStiecle && !$selectedPrice) {
  $sql = "SELECT oeuvre.titre, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom
          FROM oeuvre
          JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
          WHERE auteur.Nom = ? ";

  // Préparer la requête
  $stmt = $conn->prepare($sql);

  $stmt->bind_param('s' , $selectedAuteur);

  $stmt->execute();
  }




  if (!$selectedAuteur && $selectedStyle && !$selectedStiecle && !$selectedPrice) {
    $sql = "SELECT oeuvre.titre, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom
            FROM oeuvre
            JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
            WHERE oeuvre.Style = ? ";
  
    // Préparer la requête
    $stmt = $conn->prepare($sql);
  
    $stmt->bind_param('s' , $selectedStyle);
  
    $stmt->execute();
    }




    if (!$selectedAuteur && !$selectedStyle && !$selectedStiecle && !$selectedPrice) {
      $sql = "SELECT oeuvre.titre, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom
              FROM oeuvre
              JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur";
    
      // Préparer la requête
      $stmt = $conn->prepare($sql);
    
      $stmt->execute();
      }



      if ($selectedAuteur && $selectedStyle && $selectedStiecle && !$selectedPrice) {
        if ( $selectedStiecle == '21e' ) {
          $selectedStiecle1 = 2001;
          $selectedStiecle2 = 2100;
        }
        if ( $selectedStiecle == '20e' ) {
          $selectedStiecle1 = 1901;
          $selectedStiecle2 = 2000;
        }
        if ( $selectedStiecle == '19e' ) {
          $selectedStiecle1 = 1801;
          $selectedStiecle2 = 1900;
        }
        $sql = "SELECT oeuvre.titre, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom
                FROM oeuvre
                JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
                WHERE oeuvre.Style = ? AND auteur.Nom = ? AND oeuvre.Date_oeuvre BETWEEN ? AND ? ";
      
        // Préparer la requête
        $stmt = $conn->prepare($sql);
      
        $stmt->bind_param('ssii' , $selectedStyle, $selectedAuteur, $selectedStiecle1, $selectedStiecle2);
      
        $stmt->execute();
        }

    
    if ($selectedAuteur && !$selectedStyle && $selectedStiecle && !$selectedPrice) {
      if ( $selectedStiecle == '21e' ) {
        $selectedStiecle1 = 2001;
        $selectedStiecle2 = 2100;
      }
      if ( $selectedStiecle == '20e' ) {
        $selectedStiecle1 = 1901;
        $selectedStiecle2 = 2000;
      }
      if ( $selectedStiecle == '19e' ) {
        $selectedStiecle1 = 1801;
        $selectedStiecle2 = 1900;
      }
      $sql = "SELECT oeuvre.titre, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom
              FROM oeuvre
              JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
              WHERE auteur.Nom = ? AND oeuvre.Date_oeuvre BETWEEN ? AND ? ";
    
      // Préparer la requête
      $stmt = $conn->prepare($sql);
    
      $stmt->bind_param('sii' , $selectedAuteur, $selectedStiecle1, $selectedStiecle2);
    
      $stmt->execute();
      }
 
    
    if (!$selectedAuteur && !$selectedStyle && $selectedStiecle && !$selectedPrice) {
      if ( $selectedStiecle == '21e' ) {
        $selectedStiecle1 = 2001;
        $selectedStiecle2 = 2100;
      }
      if ( $selectedStiecle == '20e' ) {
        $selectedStiecle1 = 1901;
        $selectedStiecle2 = 2000;
      }
      if ( $selectedStiecle == '19e' ) {
        $selectedStiecle1 = 1801;
        $selectedStiecle2 = 1900;
      }
      $sql = "SELECT oeuvre.titre, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom
              FROM oeuvre
              JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
              WHERE oeuvre.Date_oeuvre BETWEEN ? AND ? ";
    
      // Préparer la requête
      $stmt = $conn->prepare($sql);
    
      $stmt->bind_param('ii' ,$selectedStiecle1, $selectedStiecle2);
    
      $stmt->execute();
      }


      if (!$selectedAuteur && $selectedStyle && $selectedStiecle && !$selectedPrice) {
        if ( $selectedStiecle == '21e' ) {
          $selectedStiecle1 = 2001;
          $selectedStiecle2 = 2100;
        }
        if ( $selectedStiecle == '20e' ) {
          $selectedStiecle1 = 1901;
          $selectedStiecle2 = 2000;
        }
        if ( $selectedStiecle == '19e' ) {
          $selectedStiecle1 = 1801;
          $selectedStiecle2 = 1900;
        }
        $sql = "SELECT oeuvre.titre, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom
                FROM oeuvre
                JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
                WHERE oeuvre.Style = ? AND auteur.Nom = ? AND oeuvre.Date_oeuvre BETWEEN ? AND ? ";
      
        // Préparer la requête
        $stmt = $conn->prepare($sql);
      
        $stmt->bind_param('sii' , $selectedStyle, $selectedStiecle1, $selectedStiecle2);
      
        $stmt->execute();
        }

$result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $oeuvres = $result->fetch_all(MYSQLI_ASSOC);
  } else {
      echo "<p>Pas d'œuvre pour le style : " . htmlspecialchars($selectedStyle) . "</p>" ;
      exit;
  }

  $query = "SELECT DISTINCT Style FROM oeuvre"; 
  $stmt = $conn->prepare($query);
  $stmt->execute();
  $result = $stmt->get_result();
  $all_styles = $result->fetch_all(MYSQLI_ASSOC);

  $query = "SELECT DISTINCT auteur.Prenom , auteur.Nom FROM oeuvre
            JOIN auteur WHERE oeuvre.Num_client_aut = auteur.Num_auteur"; 
  $stmt = $conn->prepare($query);
  $stmt->execute();
  $result = $stmt->get_result();
  $all_auteurs = $result->fetch_all(MYSQLI_ASSOC);

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);

// Supposons que vous obtenez le style depuis une requête GET ou une autre source.



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
    <form action="des_oeuvres.php" method="GET" id="style-form" class = header2>
        <select id="tkt" name="auteur">
            <option value="">Auteur</option>
            <?php foreach ($all_auteurs as $auteurItem): ?>
              <option value="<?= htmlspecialchars($auteurItem['Nom']) ?>"><?= htmlspecialchars($auteurItem['Nom']) ?></option>
            <?php endforeach; ?>
            <!-- Ajouter d'autres auteurs -->
        </select>
        <select id="price" name="price">
            <option value="">Prix</option>
            <option value="10">10 - 1 000€</option>
            <option value="1001">1 001 - 10 001€</option>
            <option value="10001">10 001 - 100 001€</option>
            <option value="100001">100 001 - 1 000 001€</option>
        </select>
        <select id="siecle" name="siecle">
            <option value="">Siècle</option>
            <option value="21e">21e siècle</option>
            <option value="20e">20e siècle</option>
            <option value="19e">19e siècle</option>
            <option value="18e">18e siècle</option>

        </select>
        <select id="style-select" name="style" onchange="document.getElementById('style-form').submit()">
          <option value=""><?php echo $selectedStyle; ?></option>
          <?php foreach ($all_styles as $styleItem): ?>
              <option value="<?= htmlspecialchars($styleItem['Style']) ?>"><?= htmlspecialchars($styleItem['Style']) ?></option>
          <?php endforeach; ?>
      </select>
          </form>

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