<?php
include 'connexion.php'; // Inclut le fichier de connexion à la base de données


// Récupérer les valeurs soumises par le formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Requête SQL pour vérifier si l'utilisateur existe dans la base
    $sql = "SELECT * FROM utilisateur WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // L'utilisateur existe, on vérifie le mot de passe
        $user = $result->fetch_assoc();

        // Vérification du mot de passe (si crypté avec password_hash)
        if ($password == $user['Mot_de_passe']){
            echo "Connexion réussie !";
            // Rediriger vers une autre page ou démarrer une session
        } else {
            echo "Mot de passe incorrect.";
            echo $user['Mot_de_passe'];
            echo $password;
        }
    } else {
        echo "Aucun utilisateur trouvé avec cet email.";
    }
}

// Fermer la connexion
$conn->close();
?>