<?php

include 'test_email.php';
include 'Connexion.php';

$style = '%';

if (isset($_GET['style']) && $_GET['style'] !== "") {
    // Récupérer le style sélectionné
    echo "Style sélectionné : " . htmlspecialchars($_GET['style']) . "<br>";
    $style = $_GET['style'];}

echo "<p>Pas d'œuvre pour le style : " . htmlspecialchars($style) . "</p>" ;

$sql = "SELECT oeuvre.titre, oeuvre.Prix_Loffre, oeuvre.Date_Loffre, oeuvre.Date_oeuvre, auteur.Prenom, auteur.Nom
        FROM oeuvre
        JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur
        WHERE (oeuvre.Style LIKE ?)";

// Préparer la requête
$stmt = $conn->prepare($sql);

$stmt->bind_param("s", $style);

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
  $all_styles = $result->fetch_all(MYSQLI_ASSOC);












?>