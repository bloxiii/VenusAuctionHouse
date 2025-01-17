<?php
session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);

// Inclure la connexion à la base de données
include 'connexion.php';

?>




<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VAH</title>
    <link rel="stylesheet" href="APP.css?v=1.113">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
</head>
<body>
    <div class="FIXE">
    <?php include('barre de recherche.php') ?>
</div>

<div class="pad">
    <div class="content-box" data-aos="fade">
        <h1>Présentation</h1>
        <p>
            Bienvenue sur <span class="highlight">Venus Auction House</span>, notre site de ventes aux enchères. Ce site a pour but de vous donner accès à une sélection d'œuvres d'art, aussi bien des peintures que des statues. Venus Auction House vous propose une vente ergonomique.
        </p>
        <p>Notre site repose sur des piliers précis :</p>
        <ul>
            <li>Offrir une <strong>visibilité accrue aux œuvres d’art</strong> : avec un catalogue diversifié et des outils de recherche intuitifs.</li>
            <li>Faciliter l’<strong>interaction entre les utilisateurs et l’art</strong> : en permettant aux utilisateurs de participer à des enchères en direct, avec des mises à jour instantanées.</li>
            <li>Créer un <strong>lien de confiance</strong> avec notre clientèle grâce à une plateforme sécurisée et une communication claire et directe via la page de contact.</li>
        </ul>
        <p>
            Notre entreprise, <span class="highlight">Wen Hive</span>, est une start-up spécialisée dans le développement de sites web. Nos membres sont : Asri Nissrine, Ba Henry, Belamy Victor, Blanchet Etienne, Duval Enzo, Ramdani Wissal. Bien que notre équipe soit petite, nous sommes efficaces et attentionnés envers notre clientèle.
        </p>
    </div>
    
    <div id="contactButton">Contactez-nous</div>

    <div id="contactPopup" class="popup">
        <div class="popup-content">
            <span class="close-btn" id="closePopup">&times;</span>
            <h2>Nous contacter</h2>
            <p><strong>Email :</strong> contact@venusauctionhouse.com</p>
            <p><strong>Téléphone :</strong> +33 1 23 45 67 89</p>
            <p><strong>Adresse :</strong> 123 Rue de Paris, 75001 Paris, France</p>
            <p><strong>Horaires :</strong> Lundi - Vendredi : 9h00 - 18h00</p>
        </div>
    </div>
     <!-- Section des enchères -->
     <div class="enchere-section" data-aos="zoom-in" >
    <h2>Les 5 dernières ventes</h2>
    <ul>
        <?php
        // Récupération des 5 dernières enchères
        $result = $conn->query("
            SELECT Num_oeuvre, Titre, Imagee, Prix_Loffre, Date_Loffre 
            FROM oeuvre 
            WHERE Date_Loffre IS NOT NULL AND Num_client_a !=0 
            ORDER BY Date_Loffre DESC 
            LIMIT 5
        ");

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "
                    <li>
                        <img src='{$row['Imagee']}' alt='{$row['Titre']}' style='width:50px; height:auto;'>
                        <strong>{$row['Titre']}</strong> - 
                        Montant : {$row['Prix_Loffre']} € - 
                        Date : {$row['Date_Loffre']}
                    </li>
                ";
            }
        } else {
            echo "<li>Aucune vente récente</li>";
        }
        ?>
    </ul>
</div>

<div class="enchere-section" data-aos="zoom-in">
    <h2>Les 5 plus grosses ventes</h2>
    <ul>
        <?php
        // Récupération des 5 plus grosses enchères
        $result = $conn->query("
            SELECT Num_oeuvre, Titre, Imagee, Prix_Loffre, Date_Loffre 
            FROM oeuvre 
            WHERE Prix_Loffre IS NOT NULL AND Num_client_a != 0 
            ORDER BY Prix_Loffre DESC 
            LIMIT 5
        ");

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "
                    <li>
                        <img src='{$row['Imagee']}' alt='{$row['Titre']}' style='width:50px; height:auto;'>
                        <strong>{$row['Titre']}</strong> - 
                        Montant : {$row['Prix_Loffre']} € - 
                        Date : {$row['Date_Loffre']}
                    </li>
                ";
            }
        } else {
            echo "<li>Aucune ventes importante</li>";
        }
        ?>
    </ul>
</div>

</div>

    <script src="APP.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
        document.addEventListener("DOMContentLoaded", function () {
            AOS.init({
                duration: 1000,
                easing: "ease-in-out",
                once: true,
            });
        });
    </script>

</body>
</html>
