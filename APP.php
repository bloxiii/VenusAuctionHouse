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
    <link rel="stylesheet" href="APP.css">
</head>
<body>
    <?php include('barre de recherche.php') ?>


    <div class="content-box">
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

    <script src="APP.js"></script>
</body>
</html>
