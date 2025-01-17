<?php


include 'Connexion.php';
session_start(); // Démarre la session
$is_logged_in = isset($_SESSION['Num_client']);


$num_client_a = $_POST['num_client_a'];
$Prix_Loffre = $_POST['bidAmount']; // Nouvelle offre
$Date_Loffre = $_POST['Date_Loffre'];
$oldOffer = $_POST['oldOffer']; // Ancienne offre
$num_oeuvre = $_POST['num_oeuvre'];

echo "Numéro du client (num_client_a) : " . $num_client_a . "<br>";
echo "Prix de l'offre (Prix_Loffre) : " . $Prix_Loffre . " €<br>";
echo "Date de l'offre (Date_Loffre) : " . $Date_Loffre . "<br>";
echo "Ancienne offre (oldOffer) : " . $oldOffer . " €<br>";
echo "Numéro de l'œuvre (num_oeuvre) : " . $num_oeuvre . "<br>";


// Vérifiez si la nouvelle offre est supérieure à l'ancienne
if ($Prix_Loffre <= $oldOffer) {
    $errorMessage = "Votre offre doit être supérieure à l'offre précédente (€" . number_format($oldOffer, 2) . ").";
    $_SESSION['errorMessage'] = $errorMessage;
    header("Location: popup.php");   #IL Y A UN PBL ICI ( ?numoeuvre= 221502) 
    exit();
}

// Préparer et exécuter la requête SQL pour enregistrer l'offre
$sql = "UPDATE oeuvre SET Num_client_LastO  = ?, Prix_Loffre = ?, Date_Loffre = ?
Where num_oeuvre = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisi", $num_client_a, $Prix_Loffre, $Date_Loffre, $num_oeuvre);

header("Location: Mes_ench_cours.php");


if ($stmt->execute()) {
    $successMessage = "Paiement enregistré avec succès !";
    $_SESSION['successMessage'] = $successMessage;
} else {
    $errorMessage = "Erreur lors de l'enregistrement : " . $stmt->error;
    $_SESSION['errorMessage'] = $errorMessage;
}

// Fermer la connexion
$stmt->close();
$conn->close();

// Rediriger vers la page avec le formulaire
exit();
?>

?>