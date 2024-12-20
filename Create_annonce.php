<?php 
include 'Connexion.php';
session_start(); // Démarre la session

$is_logged_in = isset($_SESSION['Num_client']);

?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poster une annonce</title>
    <link rel="stylesheet" href="Create.css"> <!-- Optionnel : Ajouter un fichier CSS pour styliser la page -->
</head>
<body>
    <div class="FIXE">
    <?php include('barre de recherche.php') ?>
</div>
    <h1 class="H1Z1">Poster une annonce pour une œuvre</h1>

    <form action="Publication_oeuvre.php" method="POST" enctype="multipart/form-data" class="fform">
    <h1 class="H1Z1">Poster une annonce pour une œuvre</h1>

        <div>
            <label for="titre">Titre :</label>
            <input type="text" id="titre" name="titre" required>
        </div>

        <div>
            <label for="description">Description :</label>
            <input type="text" id="description" name="description" required>
        </div>


        <div style="display: flex; gap: 10px;">
        <div>
            <label for="prenom">Prénom de l'auteur :</label>
            <input type="text" id="prenom" name="prenom" required>
        </div>
        <div>
            <label for="nom">Nom de l'auteur :</label>
            <input type="text" id="nom" name="nom" required>
        </div>
    </div>


        <div style="display: flex; gap: 10px;">
        <div>
        <label for="style">Style :</label>
        <input type="text" id="style" name="style" required>
        </div>
        <div>
            <label for="date">Date :</label>
            <input type="number" id="date" name="date" required>
        </div>
    </div>

        <div style="display: flex; gap: 10px;">
        <div>
            <label for="prix">Prix (€) :</label>
            <input type="number" id="prix" name="prix" step="0.01" required>
        </div>
        <div>
            <label for="increment">Incrément (€) :</label>
            <input type="number" id="increment" name="increment" step="0.01" required>
        </div>
    </div>

        <div>
            <label for="photo">Photo de l'œuvre :</label>
            <input type="file" id="photo" name="photo" accept="image/*" required>
        </div>


        <div style="display: flex; gap: 10px;">
        <div>
            <label for="dimension_x">Dimension X (cm) :</label>
            <input type="number" id="dimension_x" name="dimension_x" required>
        </div>
        <div>
            <label for="dimension_y">Dimension Y (cm) :</label>
            <input type="number" id="dimension_y" name="dimension_y" required>
        </div>
    </div>

        <div>
            <button type="submit">Poster l'annonce</button>
        </div>
    </form>
</body>
</html>
