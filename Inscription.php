<?php
session_start(); // Démarre la session

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['Num_client']);

// Inclure la connexion à la base de données
include 'connexion.php';

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer et nettoyer les données
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $prenom = mysqli_real_escape_string($conn, $_POST['prenom']);
    $nom = mysqli_real_escape_string($conn, $_POST['nom']);
    $adresse = mysqli_real_escape_string($conn, $_POST['adresse']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Vérifier si l'email existe déjà
    $sql = "SELECT * FROM utilisateur WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email); // 's' pour string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Cet email est déjà utilisé. Veuillez en choisir un autre.";
    } else {

        // Requête SQL pour insérer les données dans la base de données
        $sql = "INSERT INTO utilisateur (Email, Prenom, Nom, Adresse, Mot_de_passe) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $email, $prenom, $nom, $adresse, $password);

        if ($stmt->execute()) {
            echo "Compte créé avec succès !";
            // Redirection vers la page de connexion ou autre
            header("Location: SeConnecter.php");
            exit();
        } else {
            echo "Erreur : " . $conn->error;
        }
    }

    // Fermer la requête
    $stmt->close();
}

// Fermer la connexion
$conn->close();


?>



<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Venus Auction House</title>
    <link rel="stylesheet" href="Inscription.css?v=1.1" />
  </head>
  <body>
  <?php include ('barre de recherche.php'); ?>
    <main>
    <div class="form-container">
    <form class="mform" action="#" method="post">
        <label for="email"></label>
        <input
            type="email"
            id="email"
            name="email"
            placeholder="Entrez votre email"
            required
        />

        <label for="prenom"></label>
        <input
            type="text"
            id="prenom"
            name="prenom"
            placeholder="Entrez votre prénom"
            required
        />

        <label for="nom"></label>
        <input
            type="text"
            id="nom"
            name="nom"
            placeholder="Entrez votre nom"
            required
        />

        <label for="adresse"></label>
        <input
            type="text"
            id="adresse"
            name="adresse"
            placeholder="Entrez votre adresse"
            required
        />

        <label for="password"></label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Entrez votre mot de passe"
            required
        />

        <button type="submit" class="custom-button">Créer un compte</button>
        <a href="SeConnecter.php" class="forgot-password">Déjà un compte ? Se connecter</a>
    </form>
</div>
    </main>
    <script src="burger-menu.js"></script>
  </body>
</html>
