<?php use App\Csrf; ?>
<section class="dash">
  <header class="dash-header">
    <div><h2>Nutzerverwaltung</h2><p class="muted">Benutzer anlegen</p></div>
    <div class="actions"><a class="btn" href="?route=dashboard">Zurück</a></div>
  </header>

  <?php if (!empty($errors)): ?>
    <div class="alert error">
      <?php foreach ($errors as $e): ?><?= htmlspecialchars($e) ?><br><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <div class="alert success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="post" action="?route=users_create" class="form grid">
    <?= Csrf::field() ?>
    <label>E-Mail
      <input type="email" name="email" required>
    </label>
    <label>Passwort
      <input type="password" name="password" required>
      <small class="muted">Mind. 12 Zeichen, Groß/Klein, Zahl, Sonderzeichen</small>
    </label>
    <label>Rolle
      <select name="role" required>
        <option>Admin</option>
        <option>Projektleiter</option>
        <option>Techniker</option>
        <option>Mitarbeiter</option>
        <option>Lernender</option>
      </select>
    </label>
    <button class="btn primary" type="submit">Benutzer anlegen</button>
  </form>
</section>
