<?php use App\Csrf; ?>
<section class="dash">
  <header class="dash-header">
    <div>
      <h2>Passwort setzen</h2>
      <p class="muted"><?= htmlspecialchars($u['email']) ?> (<?= htmlspecialchars($u['role']) ?>)</p>
    </div>
    <div class="actions"><a class="btn" href="?route=users">Zurück</a></div>
  </header>

  <?php if (!empty($errors)): ?>
    <div class="alert error"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
  <?php endif; ?>

  <form method="post" action="?route=user_password_post&id=<?= (int)$u['id'] ?>" class="form">
    <?= Csrf::field() ?>
    <label>Neues Passwort
      <input type="password" name="new" required>
    </label>
    <label>Bestätigung
      <input type="password" name="confirm" required>
    </label>
    <p class="muted">Beachte die Passwort-Richtlinien (Länge, Komplexität).</p>
    <button class="btn primary" type="submit">Speichern</button>
  </form>
</section>
