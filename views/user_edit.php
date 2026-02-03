<?php
use App\Csrf;
$isNew = empty($u);
$roles = ['Admin','Projektleiter','LeitenderTechniker','Techniker','Mitarbeiter','Lernender'];
?>
<section class="dash" style="max-width:700px; margin:0 auto; padding:20px;">
  
  <header class="dash-header">
    <div class="dash-title">
        <h2><?= $isNew ? 'Neuen Benutzer anlegen' : 'Benutzer bearbeiten' ?></h2>
        <?php if(!$isNew): ?>
            <div class="muted">ID #<?= $u['id'] ?> · <?= htmlspecialchars($u['email']) ?></div>
        <?php endif; ?>
    </div>
    <div class="actions">
        <a href="?route=users" class="btn-icon">Abbrechen</a>
    </div>
  </header>

  <?php if (!empty($errors)): ?>
    <div style="background:#fff5f5; border-left:4px solid #d9534f; padding:15px; margin-bottom:20px; color:#c0392b; border-radius:4px;">
      <strong>Fehler:</strong>
      <ul style="margin:5px 0 0 15px; padding:0;">
          <?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= $isNew ? '?route=user_create' : '?route=user_update&id='.$u['id'] ?>">
    <?= Csrf::field() ?>

    <div class="content-card" style="margin-bottom:25px;">
        <div class="card-head"><h3>👤 Profildaten</h3></div>
        <div style="padding:20px;">
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">E-Mail Adresse (Login)</label>
                <input type="email" name="email" value="<?= htmlspecialchars($u['email'] ?? '') ?>" required <?= $isNew ? '' : 'readonly' ?> 
                       style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; background:<?= $isNew ? '#fff' : '#f9f9f9' ?>;">
                <?php if(!$isNew): ?><small class="muted">Der Login-Name kann nicht geändert werden.</small><?php endif; ?>
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Anzeigename</label>
                <input type="text" name="name" value="<?= htmlspecialchars($u['name'] ?? '') ?>" placeholder="Vorname Nachname" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
            </div>

            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px;">System-Rolle</label>
                <select name="role" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; background:#fff;">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r ?>" <?= (($u['role'] ?? '') === $r) ? 'selected':'' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="content-card" style="margin-bottom:25px;">
        <div class="card-head" style="background:#fffdf5; border-bottom:1px solid #faeccc;"><h3>🔒 Sicherheit & Zugang</h3></div>
        <div style="padding:20px;">
            
            <?php if ($isNew): ?>
                <div style="margin-bottom:20px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Initiales Passwort</label>
                    <input type="password" name="password" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                    <small class="muted">Muss beim ersten Login geändert werden (falls Policy aktiv).</small>
                </div>
            <?php else: ?>
                <div style="margin-bottom:20px; padding:15px; background:#f9f9f9; border-radius:6px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Passwort ändern</label>
                    <input type="password" name="password" placeholder="Neues Passwort..." style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                    <small style="color:#666; display:block; margin-top:5px;">Leer lassen, um das aktuelle Passwort beizubehalten.</small>
                </div>
            <?php endif; ?>

            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:10px; border:1px solid #e0e0e0; border-radius:4px; transition:background 0.2s;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='transparent'">
                <input type="checkbox" name="locked" value="1" <?= !empty($u['locked']) ? 'checked' : '' ?> style="transform:scale(1.2);">
                <div>
                    <strong style="color:#d9534f; display:block;">Zugang sperren</strong>
                    <span style="font-size:0.85em; color:#777;">Benutzer kann sich nicht mehr anmelden.</span>
                </div>
            </label>
        </div>
    </div>

    <div style="text-align:right; display:flex; justify-content:space-between; align-items:center;">
        <?php if(!$isNew): ?>
            <button type="submit" name="delete" value="1" onclick="return confirm('Benutzer wirklich löschen?');" style="background:none; border:none; color:#d9534f; text-decoration:underline; cursor:pointer;">Benutzer löschen</button>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
        
        <button type="submit" class="btn-primary" style="padding:12px 30px; border:none; border-radius:4px; cursor:pointer; font-size:1em; background:#0056b3; color:white;">
            <?= $isNew ? 'Benutzer erstellen' : 'Speichern' ?>
        </button>
    </div>

  </form>
</section>