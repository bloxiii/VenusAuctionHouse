<?php
include 'Connexion.php';

session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);
$selectedStyle = null;
$selectedPrice = null;
$selectedAuteur = null;
$selectedStiecle = null;


// Vérifie si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET['search']) ) {
  // Récupère la valeur de l'option sélectionnée
  $selectedStyle = isset($_GET['style']) ? $_GET['style'] : null;
  $selectedPrice = isset($_GET['price']) ? $_GET['price'] : null;
  $selectedAuteur = isset($_GET['auteur']) ? $_GET['auteur'] : null;
  $selectedStiecle = isset($_GET['siecle']) ? $_GET['siecle'] : null;
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
    $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
            FROM oeuvre
            JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
            WHERE oeuvre.Style = ? AND auteur.Nom = ? ";
  
    // Préparer la requête
    $stmt = $conn->prepare($sql);
  
    $stmt->bind_param('ss' , $selectedStyle, $selectedAuteur);
  
    $stmt->execute();
    }

  
if ($selectedAuteur && !$selectedStyle && !$selectedStiecle && !$selectedPrice) {
  $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
          FROM oeuvre
          JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
          WHERE auteur.Nom = ? ";

  // Préparer la requête
  $stmt = $conn->prepare($sql);

  $stmt->bind_param('s' , $selectedAuteur);

  $stmt->execute();
  }




  if (!$selectedAuteur && $selectedStyle && !$selectedStiecle && !$selectedPrice) {
    $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
            FROM oeuvre
            JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
            WHERE oeuvre.Style = ? ";
  
    // Préparer la requête
    $stmt = $conn->prepare($sql);
  
    $stmt->bind_param('s' , $selectedStyle);
  
    $stmt->execute();
    }




    if (!$selectedAuteur && !$selectedStyle && !$selectedStiecle && !$selectedPrice) {
      $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
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
        $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
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
      $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
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
      $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
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
        $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
                FROM oeuvre
                JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
                WHERE oeuvre.Style = ? AND oeuvre.Date_oeuvre BETWEEN ? AND ? ";
      
        // Préparer la requête
        $stmt = $conn->prepare($sql);
      
        $stmt->bind_param('sii' , $selectedStyle, $selectedStiecle1, $selectedStiecle2);
      
        $stmt->execute();
        }



  if (!$selectedAuteur && !$selectedStyle && !$selectedStiecle && $selectedPrice) {
    if ( $selectedPrice == '10' ) {
      $selectedPrice1 = 10;
      $selectedPrice2 = 1001;
    }
    if ( $selectedPrice == '1001' ) {
      $selectedPrice1 = 1001;
      $selectedPrice2 = 10000;
    }
    if ( $selectedPrice == '10001' ) {
      $selectedPrice1 = 10001;
      $selectedPrice2 = 100000;
    }
    if ( $selectedPrice == '100001' ) {
      $selectedPrice1 = 100001;
      $selectedPrice2 = 1000000;
    }
    $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
            FROM oeuvre
            JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
            WHERE oeuvre.Prix_Loffre BETWEEN ? AND ?  ";
  
    // Préparer la requête
    $stmt = $conn->prepare($sql);
  
    $stmt->bind_param('ii' , $selectedPrice1, $selectedPrice2);
  
    $stmt->execute();
    }


    if ($selectedAuteur && $selectedStyle && $selectedStiecle && $selectedPrice) {
      if ( $selectedPrice == '10' ) {
        $selectedPrice1 = 10;
        $selectedPrice2 = 1001;
      }
      if ( $selectedPrice == '1001' ) {
        $selectedPrice1 = 1001;
        $selectedPrice2 = 10000;
      }
      if ( $selectedPrice == '10001' ) {
        $selectedPrice1 = 10001;
        $selectedPrice2 = 100000;
      }
      if ( $selectedPrice == '100001' ) {
        $selectedPrice1 = 100001;
        $selectedPrice2 = 1000000;
      }
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
      $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
              FROM oeuvre
              JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
              WHERE oeuvre.Prix_Loffre BETWEEN ? AND ? AND oeuvre.Style = ? AND auteur.Nom = ? AND oeuvre.Date_oeuvre BETWEEN ? AND ? ";
    
      // Préparer la requête
      $stmt = $conn->prepare($sql);
    
      $stmt->bind_param('iissii' ,$selectedPrice1, $selectedPrice2, $selectedStyle,$selectedAuteur, $selectedStiecle1, $selectedStiecle2);
    
      $stmt->execute();
      }


if (!$selectedAuteur && !$selectedStyle && $selectedStiecle && $selectedPrice) {
  if ( $selectedPrice == '10' ) {
    $selectedPrice1 = 10;
    $selectedPrice2 = 1001;
  }
  if ( $selectedPrice == '1001' ) {
    $selectedPrice1 = 1001;
    $selectedPrice2 = 10000;
  }
  if ( $selectedPrice == '10001' ) {
    $selectedPrice1 = 10001;
    $selectedPrice2 = 100000;
  }
  if ( $selectedPrice == '100001' ) {
    $selectedPrice1 = 100001;
    $selectedPrice2 = 1000000;
  }
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
  $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
          FROM oeuvre
          JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
          WHERE oeuvre.Prix_Loffre BETWEEN ? AND ? AND oeuvre.Date_oeuvre BETWEEN ? AND ? ";

  // Préparer la requête
  $stmt = $conn->prepare($sql);

  $stmt->bind_param('iiii' ,$selectedPrice1, $selectedPrice2, $selectedStiecle1, $selectedStiecle2);

  $stmt->execute();
  }

  if (!$selectedAuteur && $selectedStyle && !$selectedStiecle && $selectedPrice) {
    if ( $selectedPrice == '10' ) {
      $selectedPrice1 = 10;
      $selectedPrice2 = 1001;
    }
    if ( $selectedPrice == '1001' ) {
      $selectedPrice1 = 1001;
      $selectedPrice2 = 10000;
    }
    if ( $selectedPrice == '10001' ) {
      $selectedPrice1 = 10001;
      $selectedPrice2 = 100000;
    }
    if ( $selectedPrice == '100001' ) {
      $selectedPrice1 = 100001;
      $selectedPrice2 = 1000000;
    }
    $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
            FROM oeuvre
            JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
            WHERE oeuvre.Prix_Loffre BETWEEN ? AND ? AND oeuvre.Style = ? ";
  
    // Préparer la requête
    $stmt = $conn->prepare($sql);
  
    $stmt->bind_param('iis' ,$selectedPrice1, $selectedPrice2, $selectedStyle);
  
    $stmt->execute();
    }

    if ($selectedAuteur && !$selectedStyle && !$selectedStiecle && $selectedPrice) {
      if ( $selectedPrice == '10' ) {
        $selectedPrice1 = 10;
        $selectedPrice2 = 1001;
      }
      if ( $selectedPrice == '1001' ) {
        $selectedPrice1 = 1001;
        $selectedPrice2 = 10000;
      }
      if ( $selectedPrice == '10001' ) {
        $selectedPrice1 = 10001;
        $selectedPrice2 = 100000;
      }
      if ( $selectedPrice == '100001' ) {
        $selectedPrice1 = 100001;
        $selectedPrice2 = 1000000;
      }
      $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
              FROM oeuvre
              JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
              WHERE oeuvre.Prix_Loffre BETWEEN ? AND ? AND auteur.Nom = ? ";
    
      // Préparer la requête
      $stmt = $conn->prepare($sql);
    
      $stmt->bind_param('iis' ,$selectedPrice1, $selectedPrice2, $selectedStyle,$selectedAuteur, $selectedStiecle1, $selectedStiecle2);
    
      $stmt->execute();
      }


if (!$selectedAuteur && $selectedStyle && $selectedStiecle && $selectedPrice) {
  if ( $selectedPrice == '10' ) {
    $selectedPrice1 = 10;
    $selectedPrice2 = 1001;
  }
  if ( $selectedPrice == '1001' ) {
    $selectedPrice1 = 1001;
    $selectedPrice2 = 10000;
  }
  if ( $selectedPrice == '10001' ) {
    $selectedPrice1 = 10001;
    $selectedPrice2 = 100000;
  }
  if ( $selectedPrice == '100001' ) {
    $selectedPrice1 = 100001;
    $selectedPrice2 = 1000000;
  }
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
  $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
          FROM oeuvre
          JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
          WHERE oeuvre.Prix_Loffre BETWEEN ? AND ? AND oeuvre.Style = ? AND oeuvre.Date_oeuvre BETWEEN ? AND ? ";

  // Préparer la requête
  $stmt = $conn->prepare($sql);

  $stmt->bind_param('iisii' ,$selectedPrice1, $selectedPrice2, $selectedStyle, $selectedStiecle1, $selectedStiecle2);

  $stmt->execute();
  }


  if ($selectedAuteur && !$selectedStyle && $selectedStiecle && $selectedPrice) {
    if ( $selectedPrice == '10' ) {
      $selectedPrice1 = 10;
      $selectedPrice2 = 1001;
    }
    if ( $selectedPrice == '1001' ) {
      $selectedPrice1 = 1001;
      $selectedPrice2 = 10000;
    }
    if ( $selectedPrice == '10001' ) {
      $selectedPrice1 = 10001;
      $selectedPrice2 = 100000;
    }
    if ( $selectedPrice == '100001' ) {
      $selectedPrice1 = 100001;
      $selectedPrice2 = 1000000;
    }
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
    $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
            FROM oeuvre
            JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
            WHERE oeuvre.Prix_Loffre BETWEEN ? AND ? AND auteur.Nom = ? AND oeuvre.Date_oeuvre BETWEEN ? AND ? ";
  
    // Préparer la requête
    $stmt = $conn->prepare($sql);
  
    $stmt->bind_param('iisii' ,$selectedPrice1, $selectedPrice2, $selectedAuteur, $selectedStiecle1, $selectedStiecle2);
  
    $stmt->execute();
    }

    if ($selectedAuteur && $selectedStyle && !$selectedStiecle && $selectedPrice) {
      if ( $selectedPrice == '10' ) {
        $selectedPrice1 = 10;
        $selectedPrice2 = 1001;
      }
      if ( $selectedPrice == '1001' ) {
        $selectedPrice1 = 1001;
        $selectedPrice2 = 10000;
      }
      if ( $selectedPrice == '10001' ) {
        $selectedPrice1 = 10001;
        $selectedPrice2 = 100000;
      }
      if ( $selectedPrice == '100001' ) {
        $selectedPrice1 = 100001;
        $selectedPrice2 = 1000000;
      }
      $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
              FROM oeuvre
              JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
              WHERE oeuvre.Prix_Loffre BETWEEN ? AND ? AND oeuvre.Style = ? AND auteur.Nom = ? ";
    
      // Préparer la requête
      $stmt = $conn->prepare($sql);
    
      $stmt->bind_param('iiss' ,$selectedPrice1, $selectedPrice2, $selectedStyle, $selectedAuteur);
    
      $stmt->execute();
      }



      if (isset($_GET['search']) && !empty($_GET['search'])) {
        include 'Connexion.php';
        $searchQuery = trim($_GET['search']);
        $searchmodif = ($searchQuery . '%');
        // Définir la requête SQL avec des paramètres
        $sql = "SELECT oeuvre.titre, Prix_de_depart_euro,  Imagee, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Prix_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom, Num_oeuvre
                FROM oeuvre
                JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
                WHERE oeuvre.titre LIKE ?";
    
        // Préparer la requête


        $stmt = $conn->prepare($sql);
        
        // Ajouter le paramètre pour la recherche avec un wildcard '%' pour la recherche partielle

        $stmt->bind_param('s' ,$searchmodif);

        $stmt->execute();
    }



$result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $oeuvres = $result->fetch_all(MYSQLI_ASSOC);
  } else {
      $oeuvres = [];
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
    <div class = "FIXE">
    <header class="header">
      <div class="header-container">
        <img src="logo.png" alt="Venus Auction House Logo" class="logo" />
        <form method="GET" action="">
        <input
      type="search"
      name="search"
      placeholder="Barre de recherche"
      class="search-bar"
      value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
    />
    </form>

      </div>
      <nav>
 <?php include 'Navigation/nav.php' ?>
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
            <option value="1001">1 001 - 10 000€</option>
            <option value="10001">10 001 - 100 000€</option>
            <option value="100001">100 001 - 1 000 000€</option>
        </select>
        <select id="siecle" name="siecle">
            <option value="">Siècle</option>
            <option value="21e">21e siècle</option>
            <option value="20e">20e siècle</option>
            <option value="19e">19e siècle</option>
            <option value="18e">18e siècle</option>

        </select>
        <select id="style-select" name="style">
          <option value="">Style</option>
          <?php foreach ($all_styles as $styleItem): ?>
              <option value="<?= htmlspecialchars($styleItem['Style']) ?>"><?= htmlspecialchars($styleItem['Style']) ?></option>
          <?php endforeach; ?>
      </select>

      <!-- Bouton de soumission -->
    <button type="submit">Appliquer les filtres</button>
          </form>
          </div>

    <!-- Conteneur pour les cartes d'œuvres -->
    <div class="gallery">
        <?php foreach ($oeuvres as $oeuvre): ?>
          <div class="card">
            <a href="page_de_oeuvre.php?Num_oeuvre=<?= urlencode($oeuvre['Num_oeuvre']) ?>" class="card-link">
            
                <div class="card-content" style="display: flex; align-items: flex-start;">
    <img src="<?= htmlspecialchars($oeuvre['Imagee']) ?>" alt="<?= htmlspecialchars($oeuvre['titre']) ?>" style="max-width: 180px; height : 260px; margin-right: 10px;">

    <div class ="txt">
        <h3><?= htmlspecialchars($oeuvre['titre']) ?></h3>
        <p>Date de l'œuvre : <?= htmlspecialchars($oeuvre['Date_oeuvre']) ?></p>
        <p>Auteur : <?= htmlspecialchars($oeuvre['Prenom']) ?> <?= htmlspecialchars($oeuvre['Nom']) ?></p>
        <p>Dernière offre : <?= $oeuvre['Prix_Loffre'] != 0 
        ? number_format(htmlspecialchars($oeuvre['Prix_Loffre']), 0, ',', ' ') . ' €' 
        : 'N/A' ?></p>
        <p>Montant de l'offre : <?= $oeuvre['Prix_Loffre'] != 0 
        ? number_format(htmlspecialchars($oeuvre['Prix_Loffre']), 0, ',', ' ') . ' €' 
        : number_format(htmlspecialchars($oeuvre['Prix_de_depart_euro']), 0, ',', ' ') . ' €' ?></p>
    </div>
</div>


            </div>
        <?php endforeach; ?>
    </div>
    
    <script src="burger-menu.js"></script>
    <script src="Filter_style.js"></script>
  </body>
</html>