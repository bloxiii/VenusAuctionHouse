<?php

include 'Connexion.php';

session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = $_SESSION['Num_client'];
// Récupération des données du formulaire
$titre = $_POST['titre'];
$date = $_POST['date'];
$auteurP = $_POST['prenom'];
$auteurN = $_POST['nom'];
$style = $_POST['style'];
$prix = $_POST['prix'];
$increment = $_POST['increment'];
$dimension_x = $_POST['dimension_x'];
$dimension_y = $_POST['dimension_y'];
$description = $_POST['description'];

// Gestion de l'upload de la photo
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $photoTemp = $_FILES['photo']['tmp_name'];
    $photoName = basename($_FILES['photo']['name']);
    $photoPath = 'Images/' . $photoName;

    if (!move_uploaded_file($photoTemp, $photoPath)) {
        die("Erreur lors du téléchargement de l'image.");
    }
} else {
    die("Veuillez télécharger une photo valide.");
}


    $conn->begin_transaction();

    // Vérifier si l'auteur existe déjà
    $checkAuteurSQL = "SELECT Num_auteur FROM auteur WHERE Nom = ?";
    $checkAuteurStmt = $conn->prepare($checkAuteurSQL);
    $checkAuteurStmt->bind_param('s', $auteurN);
    $checkAuteurStmt->execute();
    $checkAuteurStmt->bind_result($id_auteur);
    $checkAuteurStmt->fetch();
    $checkAuteurStmt->close();

    if (!$id_auteur) {
        // Insérer un nouvel auteur
        $insertAuteurSQL = "INSERT INTO auteur (Nom, Prenom) VALUES (?,?)";
        $insertAuteurStmt = $conn->prepare($insertAuteurSQL);
        $insertAuteurStmt->bind_param('ss', $auteurN ,$auteurP);

        if (!$insertAuteurStmt->execute()) {
            throw new Exception("Erreur lors de l'insertion de l'auteur : " . $conn->error);
        }

        $id_auteur = $conn->insert_id;
        $insertAuteurStmt->close();
    }


    $insertOeuvreSQL = "INSERT INTO oeuvre (titre, Date_oeuvre, Num_client_aut, Style, Prix_de_depart_euro, Increment, Imagee, Dimension_largeur_cm, Dimension_longueur_cm, Num_client_v, Description)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,? )";
$insertOeuvreStmt = $conn->prepare($insertOeuvreSQL);
$insertOeuvreStmt->bind_param('siisiisiiis', $titre, $date, $id_auteur, $style, $prix, $increment, $photoPath, $dimension_x, $dimension_y, $is_logged_in, $description);

if (!$insertOeuvreStmt->execute()) {
throw new Exception("Erreur lors de l'insertion de l'œuvre : " . $conn->error);
}

$conn->commit();
    header("Location: Mes_annonces.php");
    exit;
?>

