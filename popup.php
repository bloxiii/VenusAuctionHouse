<?php

include 'Connexion.php';
session_start(); // Démarre la session
$is_logged_in = isset($_SESSION['Num_client']);

// Initialisation des variables pour afficher des messages
$errorMessage = '';
$successMessage = '';


if (isset($_GET['Num_oeuvre'])) {
    $num_oeuvre = $_GET['Num_oeuvre'];

    // Requête pour récupérer les informations de l'œuvre
    $sql = "SELECT * FROM oeuvre JOIN auteur ON oeuvre.Num_client_aut = auteur.Num_auteur WHERE Num_oeuvre = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $num_oeuvre);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $oeuvre = $result->fetch_assoc(); // Récupère les données de l'œuvre
    } else {
        echo "Œuvre introuvable.";
    }
}
$resultat = !empty($oeuvre['Prix_Loffre']) 
    ? $oeuvre['Prix_Loffre'] + $oeuvre['Increment'] 
    : $oeuvre['Prix_de_depart_euro'];
// Vérification si le formulaire est soumis





?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'enchères - Paiement</title>
    <link rel="stylesheet" href="popup.css">
</head>
<body>
<?php include ('barre de recherche.php'); ?>

    <!-- Contenu principal de la page -->
    <div class="container">
        <h1>Règlement de votre achat d'œuvre d'art</h1>

        <div class="payment-form">
            <h2>Informations de Paiement</h2>
            <form method="POST" action="process_paiment.php">
                <label for="cardNumber">Numéro de carte :</label>
                <input type="text" id="cardNumber" name="cardNumber" placeholder="1234 5678 9101 1121" required>

                <label for="cardExpiry">Date d'expiration :</label>
                <input type="text" id="cardExpiry" name="cardExpiry" placeholder="MM/AA" required>

                <label for="cardCVC">CVC :</label>
                <input type="text" id="cardCVC" name="cardCVC" placeholder="123" required>

                <label for="bidAmount">Votre offre (€) :</label>
                <input type="number" id="bidAmount" name="bidAmount" placeholder="Entrer votre offre" required>
                <input type="hidden" name="num_client_a" value="<?php echo $_SESSION['Num_client'] ?>"> <!-- Exemple : ID du client -->
                <input type="hidden" name="oldOffer" value="<?php echo $resultat ?>">    <!-- Exemple : Prix de l'offre -->
                <input type="hidden" name="Date_Loffre" value="<?php echo date('Y-m-d H:i:s'); ?>"> <!-- Date actuelle -->
                <input type="hidden" name="num_oeuvre" value="<?php echo $oeuvre['Num_oeuvre'] ?>">
                <p style="color: red;">
                    <?php echo $errorMessage; ?>
                </p>
                <p style="color: green;">
                    <?php echo $successMessage; ?>
                </p>

                <button type="submit">Payer</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>

