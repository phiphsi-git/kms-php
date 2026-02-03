<?php use App\Auth; use App\Config; $u = Auth::user(); ?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars(Config::APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <?php $cssUrl = \App\Config::BASE_URL . 'assets/styles.css?v=' . time(); ?>
  <link rel="stylesheet" href="<?= htmlspecialchars($cssUrl) ?>">
  <style>
    .search-container { position: relative; margin-left: 20px; }
    #globalSearch { padding: 6px 12px; border-radius: 20px; border: 1px solid #ccc; font-size: 0.9em; width: 250px; background: #f9f9f9; transition: width 0.2s; }
    #globalSearch:focus { width: 300px; background: #fff; border-color: #0056b3; outline: none; }
    #globalSearchResult { position: absolute; top: 100%; left: 0; width: 100%; min-width: 300px; background: white; border: 1px solid #ddd; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: none; z-index: 1000; border-radius: 6px; overflow: hidden; margin-top: 5px; }
    .search-item { display: flex; align-items: center; gap: 10px; padding: 10px; border-bottom: 1px solid #eee; text-decoration: none; color: #333; font-size: 0.9em; }
    .search-item:last-child { border-bottom: none; }
    .search-item:hover { background: #f0f7ff; }
    .search-item strong { display: block; }
    .search-item small { color: #888; display: block; }
  </style>
</head>
<body>
<nav class="topnav">
  <a class="brand" href="?route=dashboard">KMS</a>
  <a href="?route=dashboard" class="<?= ($_GET['route'] ?? '')==='dashboard' ? 'active' : '' ?>">Dashboard</a>
  <a href="?route=customers" class="<?= ($_GET['route'] ?? '')==='customers' ? 'active' : '' ?>">Kunden</a>
  <?php if (\App\Auth::check()): ?>
    <div class="search-container"><input type="text" id="globalSearch" placeholder="🔍 Suchen..."><div id="globalSearchResult"></div></div>
    <a href="?route=account" class="<?= ($_GET['route'] ?? '')==='account' ? 'active' : '' ?>" style="margin-left:auto;">Konto</a>
    <?php if (\App\Policy::can('users.manage') || \App\Policy::can('users.view') || (\App\Auth::user()['role'] ?? '') === 'Admin'): ?>
      <a href="?route=users" class="<?= ($_GET['route'] ?? '')==='users' ? 'active' : '' ?>">Nutzer</a>
    <?php endif; ?>
    <form method="post" action="?route=logout" style="display:inline; margin-left:10px;"><?= \App\Csrf::field() ?><button class="btn" type="submit">Abmelden</button></form>
  <?php endif; ?>
</nav>
<main class="container"><?= $content ?? '' ?></main>
<footer class="footer"><span><?= htmlspecialchars(Config::APP_NAME) ?></span></footer>
<script>
const sInput = document.getElementById('globalSearch'); const sRes = document.getElementById('globalSearchResult');
if(sInput){
    sInput.addEventListener('input', function() {
        if(this.value.length < 2) { sRes.style.display='none'; return; }
        fetch('?route=search_api&q=' + encodeURIComponent(this.value)).then(res => res.json()).then(data => {
            sRes.innerHTML = '';
            if(data.length === 0) sRes.innerHTML = '<div style="padding:10px; color:#888;">Nichts gefunden.</div>';
            else {
                data.forEach(item => {
                    let url='#', icon='🔹';
                    if(item.type==='customer') { url='?route=customer_view&id='+item.id; icon='🏢'; }
                    if(item.type==='system') { url='?route=system_edit&id='+item.id; icon='💻'; }
                    if(item.type==='task') { url='?route=task_edit&id='+item.id; icon='✅'; }
                    sRes.innerHTML += `<a href="${url}" class="search-item"><span style="font-size:1.2em;">${icon}</span><div><strong>${item.title}</strong><small>${item.info||''}</small></div></a>`;
                });
            }
            sRes.style.display = 'block';
        });
    });
    document.addEventListener('click', function(e) { if (e.target !== sInput && e.target !== sRes) sRes.style.display = 'none'; });
}
</script>
</body>
</html>