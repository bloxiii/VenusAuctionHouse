
<?php
if (isset($_SESSION['Num_client'])) {
  $id_utilisateur = $_SESSION['Num_client']; // Récupérer l'ID utilisateur depuis la session
} else {
  $id_utilisateur = "toto"; // Définir une valeur par défaut si la session n'existe pas
}
?>




<div class="burger-menu" id="burger-menu">
          <!-- Icone du burger (simple bouton pour le moment) -->
          <button id="burger-btn">☰</button>
        </div>
        <!-- Menu caché initialement -->
        <?php if ($id_utilisateur == 0): ?>
          <ul class="ul" id="menu" style="display: none">
          <li>
          <a href="APP.php">Accueil</a>
          <a href="Mon_compte.php">Mon compte</a>
          <a href="admin.php">Admin</a>
        </li>

          <li>
            <a href="des_oeuvres.php">Oeuvre à vendre</a>
          </li>
          <li><a href="FAQ.php">FAQ</a>
          <a href="MentionsLegalesCGU.php">CGU</a></li>
          <li><a href="Logout.php">Se déconnecter</a></li>
          </ul>
        <?php endif; ?>
        <?php if ($is_logged_in): ?>
        <ul class="ul" id="menu" style="display: none">
          <li>
          <a href="APP.php">Accueil</a>
          <a href="Mon_compte.php">Mon compte</a></li>
          <li>
            <a href="des_oeuvres.php">Oeuvre à vendre</a>
            <a href="Mes_ench_cours.php">Mes enchères en cours</a>
            <a href="Mes_achats.php">Mes achats</a>
          </li>
          <li>
            <a href="Mes_annonces.php">Mes annonces</a>
            <a href="Mes_ventes.php">Mes ventes</a>
          </li>
          <li><a href="FAQ.php">FAQ</a>
          <a href="MentionsLegalesCGU.php">CGU</a></li>
          <li><a href="Logout.php">Se déconnecter</a></li>
          </ul>
          <?php else: ?>
        <!-- Menu pour les utilisateurs invités -->
        <ul class="ul" id="menu" style="display: none">
        <li>
          <a href="APP.php">Accueil</a>
          </li>
          <li>
            <a href="SeConnecter.php">Connexion</a>
            <a href="Inscription.php">Inscription</a>
          </li>
        <li><a href="des_oeuvres.php">Oeuvre à vendre</a></li>
        <li><a href="FAQ.php">FAQ</a>
        <a href="MentionsLegalesCGU.php">CGU</a></li>
        </ul>
    <?php endif; ?>


