<?php use App\Csrf; ?>
<section class="dash">
  <header class="dash-header">
    <div><h2>Passwort ändern</h2><p class="muted">Mind. 12 Zeichen, Groß/Klein, Zahl, Sonderzeichen</p></div>
    <div class="actions"><a class="btn" href="?route=account">Zurück</a></div>
  </header>

  <?php if (!empty($flash['success'])): ?>
    <div class="alert success"><?= htmlspecialchars($flash['success']) ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="alert error"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
  <?php endif; ?>

  <form method="post" action="?route=account_password_post" class="form">
    <?= Csrf::field() ?>
    <label>Aktuelles Passwort
      <input type="password" name="current" required>
    </label>
    <label>Neues Passwort
      <input type="password" name="new" required>
    </label>
    <label>Neues Passwort (Bestätigung)
      <input type="password" name="confirm" required>
    </label>
    <button class="btn primary" type="submit">Speichern</button>
  </form>
</section>
