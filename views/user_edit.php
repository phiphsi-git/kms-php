<?php
use App\Csrf;

$isNew = empty($u); // $u == null → neu
$roles = ['Admin','Projektleiter','Techniker','Mitarbeiter','Lernender'];
?>
<section class="card" style="max-width:640px;margin:0 auto">
  <h3><?= $isNew ? 'Neuen Benutzer anlegen' : 'Benutzer bearbeiten' ?></h3>

  <?php if (!empty($errors)): ?>
    <div class="alert danger">
      <?php foreach ($errors as $e): ?>
        <div><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= $isNew ? '?route=user_create' : '?route=user_update&id='.(int)$u['id'] ?>">
    <?= Csrf::field() ?>

    <label>E-Mail (Login)
      <input type="email" name="email" value="<?= htmlspecialchars($u['email'] ?? '') ?>" required <?= $isNew ? '' : 'readonly' ?>>
    </label>

    <label>Rolle
      <select name="role" required>
        <?php foreach ($roles as $r): ?>
          <option value="<?= $r ?>" <?= (($u['role'] ?? '') === $r) ? 'selected':'' ?>><?= $r ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <?php if ($isNew): ?>
      <label>Initiales Passwort
        <input type="password" name="password" placeholder="Sicheres Passwort…" required>
        <small class="muted">Mind. 8 Zeichen, Groß/Klein, Zahl, Sonderzeichen (gemäß Policy).</small>
      </label>
    <?php else: ?>
      <details style="margin:6px 0">
        <summary>Passwort ändern (optional)</summary>
        <label>Neues Passwort
          <input type="password" name="password" placeholder="Nur ausfüllen, wenn ändern">
        </label>
      </details>
    <?php endif; ?>

    <label class="flex" style="gap:8px;align-items:center">
      <input type="checkbox" name="locked" value="1" <?= !empty($u['locked']) || (isset($u['is_active']) && (int)$u['is_active']===0) ? 'checked':'' ?>>
      <span>Sperre (Benutzer deaktiviert)</span>
    </label>

    <div class="actions" style="margin-top:12px">
      <a class="btn" href="?route=users">Abbrechen</a>
      <button class="btn primary" type="submit"><?= $isNew ? 'Anlegen' : 'Speichern' ?></button>
    </div>
  </form>
</section>
