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
    <title>FAQ - Vente aux enchères</title>
    <link rel="stylesheet" href="FAQ.css">
</head>
<body>
<header>
      <div class="header-container">
        <img src="Logo.png" alt="Venus Auction House Logo" class="logo" />
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
    <div class = " padding " >
    <div class="faq-container">
        <h1>Bienvenue sur notre page FAQ</h1>
        <p>Où nous répondons aux questions fréquentes sur la participation à nos ventes aux enchères d'œuvres d'art en ligne.</p>

        <div class="faq-section" onclick="toggleFAQ('generalites')">
            <h2>1. Généralités sur la plateforme</h2>
        </div>
        <div id="generalites" class="faq-content">
            <p><strong>Q :</strong> Qu'est-ce que cette plateforme ?</p>
            <p><strong>R :</strong> Notre plateforme est un site dédié à la vente aux enchères d'œuvres d'art, permettant aux collectionneurs et amateurs d'art d'acquérir des pièces uniques.</p>
            <p><strong>Q :</strong> Dois-je créer un compte pour utiliser la plateforme ?</p>
            <p><strong>R :</strong> Oui, vous devez créer un compte gratuit pour enchérir ou vendre des œuvres d'art.</p>
            <p><strong>Q :</strong> Les œuvres proposées sont-elles authentiques ?</p>
            <p><strong>R :</strong> Toutes nos œuvres sont vérifiées et accompagnées d'un certificat d'authenticité.</p>
        </div>

        <div class="faq-section" onclick="toggleFAQ('participation')">
            <h2>2. Participation aux enchères</h2>
        </div>
        <div id="participation" class="faq-content">
            <p><strong>Q :</strong> Comment m'inscrire à une vente aux enchères ?</p>
            <p><strong>R :</strong> Une fois votre compte créé, accédez à la section des ventes en cours, sélectionnez celle qui vous intéresse et cliquez sur "Participer".</p>
            <p><strong>Q :</strong> Puis-je annuler une enchère ?</p>
            <p><strong>R :</strong> Non, une fois une enchère placée, elle ne peut pas être annulée. Veuillez vérifier attentivement avant de confirmer.</p>
            <p><strong>Q :</strong> Puis-je enchérir à tout moment ?</p>
            <p><strong>R :</strong> Oui, nos ventes aux enchères en ligne sont disponibles 24h/24, avec une date limite pour chaque lot.</p>
        </div>

        <div class="faq-section" onclick="toggleFAQ('paiements')">
            <h2>3. Paiements et livraison</h2>
        </div>
        <div id="paiements" class="faq-content">
            <p><strong>Q :</strong> Quels modes de paiement acceptez-vous ?</p>
            <p><strong>R :</strong> Nous acceptons les paiements par carte bancaire, PayPal, et virement bancaire sécurisé.</p>
            <p><strong>Q :</strong> Quand dois-je payer après avoir remporté une enchère ?</p>
            <p><strong>R :</strong> Le paiement doit être effectué dans un délai de 48 heures après la fin de la vente.</p>
            <p><strong>Q :</strong> Combien coûte la livraison ?</p>
            <p><strong>R :</strong> Les frais de livraison varient selon la taille et le poids de l'œuvre. Un devis sera fourni après la vente.</p>
        </div>

        <div class="faq-section" onclick="toggleFAQ('retours')">
            <h2>4. Politique de retour et garanties</h2>
        </div>
        <div id="retours" class="faq-content">
            <p><strong>Q :</strong> Puis-je retourner une œuvre si elle ne correspond pas à mes attentes ?</p>
            <p><strong>R :</strong> Les retours sont acceptés uniquement en cas de non-conformité avec la description ou d’endommagement pendant la livraison.</p>
            <p><strong>Q :</strong> Que faire si l'œuvre arrive endommagée ?</p>
            <p><strong>R :</strong> Contactez-nous dans les 24 heures suivant la réception avec des photos de l'emballage et de l'œuvre pour initier une réclamation.</p>
            <p><strong>Q :</strong> Les œuvres sont-elles garanties ?</p>
            <p><strong>R :</strong> Oui, nous offrons une garantie d'authenticité et une assurance contre les dommages pendant le transport.</p>
        </div>
    </div>
    </div>
    <script src="script.js"></script>
    <script src="burger-menu.js"></script>
</body>
</html>
