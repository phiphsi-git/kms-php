<?php use App\Auth; use App\Config; $u = Auth::user(); ?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars(Config::APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
	<?php
	  // ganz oben im <head> deiner layout.php
	  $cssPath = __DIR__ . '/../public/assets/styles.css'; // Server-Pfad
	  $cssUrl  = \App\Config::BASE_URL . 'assets/styles.css?v=' . (file_exists($cssPath) ? filemtime($cssPath) : time());
	?>
	<link rel="stylesheet" href="<?= htmlspecialchars($cssUrl) ?>">
</head>
<body>
<nav class="topnav">
  <a class="brand" href="?route=dashboard">KMS</a>

  <a href="?route=dashboard" class="<?= ($_GET['route'] ?? '')==='dashboard' ? 'active' : '' ?>">Dashboard</a>
  <a href="?route=customers" class="<?= ($_GET['route'] ?? '')==='customers' ? 'active' : '' ?>">Kunden</a>

  <?php if (\App\Auth::check()): ?>
    <a href="?route=account" class="<?= ($_GET['route'] ?? '')==='account' ? 'active' : '' ?>">Persönliches Konto</a>

    <?php // Nutzerverwaltung (nur für berechtigte Rollen anzeigen)
    if (\App\Policy::can('users.manage') || \App\Policy::can('users.view') || (\App\Auth::user()['role'] ?? '') === 'Admin'): ?>
      <a href="?route=users" class="<?= ($_GET['route'] ?? '')==='users' ? 'active' : '' ?>">Nutzerverwaltung</a>
    <?php endif; ?>
  <?php endif; ?>

  <div class="right">
    <?php if (\App\Auth::check()): ?>
      <form method="post" action="?route=logout" style="display:inline">
        <?= \App\Csrf::field() ?>
        <button class="btn" type="submit">Abmelden</button>
      </form>
    <?php endif; ?>
  </div>
</nav>

  <main class="container">
    <?= $content ?? '' ?>
  </main>
  <footer class="footer"><span><?= htmlspecialchars(Config::APP_NAME) ?></span></footer>
    <svg xmlns="http://www.w3.org/2000/svg" style="display:none">
	  <symbol id="i-open" viewBox="0 0 24 24">
		<path d="M10 3H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-5"/>
		<path d="M15 3h6v6"/>
		<path d="M10 14L21 3"/>
	  </symbol>
	  <symbol id="i-download" viewBox="0 0 24 24">
		<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
		<path d="M7 10l5 5 5-5"/>
		<path d="M12 15V3"/>
	  </symbol>
	  <symbol id="i-trash" viewBox="0 0 24 24">
		<path d="M3 6h18"/>
		<path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
		<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
		<path d="M10 11v6M14 11v6"/>
	  </symbol>
	  <symbol id="i-edit" viewBox="0 0 24 24">
		<path d="M12 20h9"/>
		<path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
	  </symbol>
	  <symbol id="i-plus" viewBox="0 0 24 24">
	    <path d="M12 5v14M5 12h14"/>
	  </symbol>
	  <symbol id="i-require" viewBox="0 0 24 24">
		<!-- kleines „Shield/Check“-Icon als Pflicht-Indikator -->
		<path d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4z"/>
		<path d="M9 12l2 2 4-4"/>
	  </symbol>
	</svg>

</body>
</html>
