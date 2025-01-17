
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venus Auction House</title>
    <link rel="stylesheet" href="Barre de recherche.css?v=1.122">
</head>
<body>
<header class="header">
      <div class="header-container">
        <img src="logo.png" alt="Venus Auction House Logo" class="logo" />
        <form method="GET" action="des_oeuvres.php" class="search-form">
      <input
        type="search"
        id="search"
        name="search"
        placeholder="Barre de recherche"
        class="search-bar"
      />

    </form>
      </div>
      <nav>
      <?php include 'Navigation/nav.php' ?>
      </nav>
    </header>
</body>
<script src="burger-menu.js"></script>
</html>