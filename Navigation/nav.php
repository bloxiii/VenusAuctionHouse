<div class="burger-menu" id="burger-menu">
          <!-- Icone du burger (simple bouton pour le moment) -->
          <button id="burger-btn">☰</button>
        </div>
        <!-- Menu caché initialement -->
        <?php if ($is_logged_in): ?>
        <ul id="menu" style="display: none">
          <li><a href="Mon_compte.php">Mon compte</a></li>
          <li>
            <a href="des_oeuvres.php">Oeuvre à vendre</a>
            <a href="#">Mes enchères en cours</a>
            <a href="Mes_achats.php">Mes achats</a>
          </li>
          <li>
            <a href="Mes_annonces.php">Mes annonces</a>
            <a href="Mes_ventes.php">Mes ventes</a>
          </li>
          <li><a href="FAQ.php">FAQ</a></li>
          <li><a href="Logout.php">Se déconnecter</a></li>
          </ul>
          <?php else: ?>
        <!-- Menu pour les utilisateurs invités -->
        <ul id="menu" style="display: none">
          <li>
            <a href="SeConnecter.php">Connexion</a>
            <a href="Inscription.php">Inscription</a>
          </li>
        <li><a href="des_oeuvres.php">Oeuvre à vendre</a></li>
        <li><a href="FAQ.php">FAQ</a></li>
        </ul>
    <?php endif; ?>


