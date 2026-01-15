<?php $u = \App\Auth::user(); ?>
<section class="dash">
  <header class="dash-header">
    <div><h2>Persönliches Konto</h2><p class="muted"><?= htmlspecialchars($u['email']) ?> · Rolle: <?= htmlspecialchars($u['role']) ?></p></div>
    <div class="actions"><a class="btn" href="?route=account_password">Passwort ändern</a></div>
  </header>

  <div class="card">
    <h3>Profil</h3>
    <ul>
      <li>E-Mail: <?= htmlspecialchars($u['email']) ?></li>
      <li>Rolle:  <?= htmlspecialchars($u['role']) ?></li>
    </ul>
  </div>
</section>
