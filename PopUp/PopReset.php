<style>
        /* Styles pour la pop-up */
        #contactPopup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        #popupContent {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 90%;
        }

        #popupContent p {
            font-size: 16px;
            color: #333;
            margin-bottom: 20px;
        }

        #closePopup {
            padding: 10px 20px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>

<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Venus Auction House</title>
    <link rel="stylesheet" href="PopUp.css" />
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
  </head>

    <div id='contactPopup'>
        <div id='popupContent'>
            <p>Mot de passe réinitialisé avec succès.</p>
            <button id='closePopup'>OK</button>
        </div>
    </div>

    <script>
        // Affiche la pop-up au chargement
        document.getElementById('contactPopup').style.display = 'flex';

        // Fermer la pop-up avec le bouton
        document.getElementById('closePopup').addEventListener('click', function () {
            document.getElementById('contactPopup').style.display = 'none';
            window.location.href = 'SeConnecter.php'; // Redirection après fermeture
        });

        // Fermer la pop-up si l'utilisateur clique à l'extérieur
        window.addEventListener('click', function (event) {
            const popup = document.getElementById('contactPopup');
            if (event.target === popup) {
                popup.style.display = 'none';
                window.location.href = 'SeConnecter.php'; // Redirection après fermeture
            }
        });
    </script>