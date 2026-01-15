<?php use App\Csrf; ?>
<section class="dash">
  <header class="dash-header">
    <div><h2>System bearbeiten</h2></div>
    <div class="actions"><a class="btn" href="?route=customer_view&id=<?= (int)$s['customer_id'] ?>">Zurück</a></div>
  </header>

  <?php if (!empty($errors)): ?>
    <div class="alert error"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
  <?php endif; ?>

  <form method="post" action="?route=system_update&id=<?= (int)$s['id'] ?>" class="form">
    <?= Csrf::field() ?>
    <label>Systemname* <input type="text" name="name" required value="<?= htmlspecialchars($s['name']) ?>"></label>
    <div class="grid">
      <label>Typ <input type="text" name="type" value="<?= htmlspecialchars($s['type'] ?? '') ?>"></label>
      <label>Rolle <input type="text" name="role" value="<?= htmlspecialchars($s['role'] ?? '') ?>"></label>
    </div>
    <div class="grid">
      <label>Version <input type="text" name="version" value="<?= htmlspecialchars($s['version'] ?? '') ?>"></label>
      <label>Installationsdatum <input type="date" name="install_date" value="<?= htmlspecialchars($s['install_date'] ?? '') ?>"></label>
    </div>
    <label>Verantwortlicher Techniker
      <select name="responsible_technician_id">
        <option value="">– auswählen –</option>
        <?php foreach ($technicians as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= ((int)$s['responsible_technician_id'] === (int)$t['id'])?'selected':'' ?>>
            <?= htmlspecialchars($t['email']) ?> (<?= htmlspecialchars($t['role']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Notizen
      <textarea name="notes" rows="4"><?= htmlspecialchars($s['notes'] ?? '') ?></textarea>
    </label>

	<?php $files = \App\FileRepo::listBySystem((int)$s['id']); ?>
	<div class="card" style="margin-top:12px">
	  <h3>Dateien zum System (<?= count($files) ?>)</h3>
	  <?php if (!$files): ?><p class="muted">Keine Dateien verknüpft.</p>
	  <?php else: ?>
		<ul class="simple-list">
		  <?php foreach ($files as $f): ?>
			<li>
			  <div>
				<strong><?= htmlspecialchars($f['original_name']) ?></strong>
				<?php if (!empty($f['description'])): ?>
				  <div class="muted"><?= htmlspecialchars($f['description']) ?></div>
				<?php endif; ?>
			  </div>
			  <div class="actions">
				<a class="btn small" href="?route=file_preview&id=<?= (int)$f['id'] ?>">Öffnen</a>
				<a class="btn small" href="?route=file_download&id=<?= (int)$f['id'] ?>">Download</a>
			  </div>
			</li>
		  <?php endforeach; ?>
		</ul>
	  <?php endif; ?>
	</div>

    <div class="flex gap">
      <button class="btn primary" type="submit">Speichern</button>
      <form method="post" action="?route=system_delete&id=<?= (int)$s['id'] ?>" onsubmit="return confirm('System wirklich löschen?');" style="display:inline">
        <?= Csrf::field() ?>
        <button class="btn danger" type="submit">Löschen</button>
      </form>
    </div>
  </form>
</section>
