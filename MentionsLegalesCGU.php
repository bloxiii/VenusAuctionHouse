<?php 
include('Connexion.php');

session_start(); // Démarre la session

$is_logged_in = isset($_SESSION['Num_client']);


?>



<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Venus Auction House</title>
    <link rel="stylesheet" href="MentionsLegalesCGU.css?v=1.2" />
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
  </head>
  <body>
  <div class = "FIXE">
    <?php include ('barre de recherche.php'); ?>
</div>
<div class="pad" >
    <div class="text-box">
      <section id="mentions-legales">
        <h1>Mentions légales</h1>
        <p><strong>Raison sociale :</strong> Venus Auction House</p>
        <p><strong>Adresse :</strong> 123 Rue de l'Art, 75000 Paris, France</p>
        <p><strong>Téléphone :</strong> +33 1 23 45 67 89</p>
        <p><strong>Email :</strong> contact@venusauctionhouse.com</p>
        <p><strong>SIRET :</strong> 123 456 789 00010</p>
        <p>
          <strong>Directeur de publication :</strong> Jean Dupont, Président
        </p>
        <p>
          <strong>Hébergeur :</strong> Hébergement Web SARL, 456 Rue du Web,
          75001 Paris, France
        </p>
        <p>
          <strong>Propriété intellectuelle :</strong> Tous les contenus présents
          sur ce site, y compris les œuvres d'art, logos, images, textes,
          vidéos, et autres éléments sont protégés par le droit d'auteur. Toute
          reproduction, redistribution ou utilisation commerciale des contenus
          sans autorisation est interdite.
        </p>
      </section>

      <section id="cgu">
        <h1>Conditions Générales d'Utilisation</h1>

        <h2>Article 1 - Objet</h2>
        <p>
          Les présentes conditions générales d'utilisation (CGU) régissent
          l'accès et l'utilisation du site web Venus Auction House, une
          plateforme en ligne permettant la vente aux enchères et l'achat
          d'œuvres d'art.
        </p>

        <h2>Article 2 - Accès au site</h2>
        <p>
          L'accès au site est gratuit pour les utilisateurs disposant d'une
          connexion internet. Toutefois, certains services peuvent nécessiter un
          abonnement ou une inscription préalable. L'utilisateur s'engage à
          fournir des informations exactes lors de son inscription.
        </p>

        <h2>Article 3 - Utilisation du site</h2>
        <p>
          Les utilisateurs du site peuvent consulter les œuvres d'art proposées
          à la vente, participer à des enchères en ligne, acheter des œuvres ou
          proposer des œuvres à la vente. Toutes les transactions doivent
          respecter les règles établies par Venus Auction House.
        </p>

        <h2>Article 4 - Inscription et responsabilité</h2>
        <p>
          Pour pouvoir enchérir ou vendre des œuvres sur le site, l'utilisateur
          doit créer un compte en fournissant des informations précises et à
          jour. L'utilisateur est responsable de la confidentialité de ses
          identifiants de connexion et de toute activité réalisée sous son
          compte.
        </p>

        <h2>Article 5 - Enchères et achats</h2>
        <p>
          Les enchères se déroulent selon un système de prix progressif. Toute
          enchère placée engage l'utilisateur à respecter le montant de
          l'enchère jusqu'à la fin de la vente. En cas de vente remportée,
          l'utilisateur s'engage à payer le prix d'adjudication plus les frais
          éventuels.
        </p>

        <h2>Article 6 - Conditions de vente</h2>
        <p>
          Les œuvres d'art mises en vente sur Venus Auction House sont soumises
          à des conditions spécifiques définies par les vendeurs et la
          plateforme. Les acheteurs doivent s'assurer de la conformité des
          œuvres avant de participer à l'enchère.
        </p>

        <h2>Article 7 - Paiement</h2>
        <p>
          Le paiement des œuvres achetées se fait par carte bancaire, virement
          bancaire ou tout autre moyen de paiement validé par Venus Auction
          House. L'acheteur doit procéder au paiement dans les délais indiqués
          après la fin de la vente.
        </p>

        <h2>Article 8 - Responsabilité</h2>
        <p>
          Venus Auction House ne saurait être tenue responsable des erreurs,
          pertes ou dommages survenant lors des enchères, de la vente ou de la
          livraison des œuvres d'art, sauf en cas de faute lourde de la
          plateforme.
        </p>

        <h2>Article 9 - Protection des données personnelles</h2>
        <p>
          Venus Auction House respecte la législation en vigueur concernant la
          protection des données personnelles. Les informations collectées lors
          de l'inscription ou des transactions sont utilisées uniquement pour le
          bon fonctionnement de la plateforme. Pour plus de détails, veuillez
          consulter notre politique de confidentialité.
        </p>

        <h2>Article 10 - Modifications des CGU</h2>
        <p>
          Venus Auction House se réserve le droit de modifier ces conditions
          générales d'utilisation à tout moment. Les utilisateurs seront
          informés de toute modification via une notification sur le site.
        </p>

        <h2>Article 11 - Loi applicable et juridiction</h2>
        <p>
          Les présentes CGU sont régies par la loi française. En cas de litige,
          les tribunaux compétents de Paris seront seuls habilités à juger de
          l'affaire.
        </p>

        <p><strong>Date de dernière mise à jour :</strong> 11 décembre 2024</p>
      </section>
    </div>
</div>
    <script src="burger-menu.js"></script>
  </body>
</html>
