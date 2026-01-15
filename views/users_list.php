<section class="dash">
  <header class="dash-header">
    <div>
      <h2>Nutzerverwaltung</h2>
      <p class="muted">Alle Benutzer (aktiv)</p>
    </div>
	<div class="card-head-between">
	  <h3>Nutzerverwaltung</h3>

	  <?php if (\App\Policy::can('users.update')): ?>
		<a class="btn primary" href="?route=user_new">+ Neuer Benutzer</a>
	  <?php endif; ?>
	</div>
	<form method="get" action="" class="filters" style="margin-top:8px">
  <input type="hidden" name="route" value="users">
  <input type="search" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Suche nach E-Mail/Rolle …" />
  <select name="sort">
    <option value="email_asc"  <?= ($sort??'')==='email_asc'?'selected':'' ?>>E-Mail ↑</option>
    <option value="email_desc" <?= ($sort??'')==='email_desc'?'selected':'' ?>>E-Mail ↓</option>
    <option value="role_asc"   <?= ($sort??'')==='role_asc'?'selected':'' ?>>Rolle ↑</option>
    <option value="role_desc"  <?= ($sort??'')==='role_desc'?'selected':'' ?>>Rolle ↓</option>
    <option value="status_desc"<?= ($sort??'')==='status_desc'?'selected':'' ?>>Sperre (entsperrt zuerst)</option>
  </select>
  <button class="btn" type="submit">Anwenden</button>
</form>

  </header>

  <div class="table">
    <div class="thead">
      <div>E-Mail</div><div>Rolle</div><div>Sperre</div><div>Aktionen</div>
    </div>
    <?php if (empty($users)): ?>
      <div class="row"><div colspan="4">Keine Benutzer gefunden.</div></div>
    <?php else: ?>
      <?php foreach ($users as $u): ?>
        <div class="row">
          <div><?= htmlspecialchars($u['email']) ?></div>
          <div><?= htmlspecialchars($u['role']) ?></div>
          <?php
			  $active = !empty($u['is_active']);
			  $label  = $active ? 'Entsperrt' : 'Gesperrt';
			  $dotCls = $active ? 'dot--green' : 'dot--red';
			?>
			<div>
			  <span class="dot <?= $dotCls ?>" title="<?= htmlspecialchars($label) ?>"></span>
			</div>
          <div class="actions">
            <?php if (\App\Policy::can('users.manage')): ?>
              <a class="btn" href="?route=user_edit&id=<?= (int)$u['id'] ?>">Bearbeiten</a>
            <?php endif; ?>
            <?php
              $actor = \App\Auth::user();
              if ($actor && \App\Policy::canResetPasswordOf($actor, $u)):
            ?>
              <a class="btn" href="?route=user_password&id=<?= (int)$u['id'] ?>">Passwort setzen</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<style>
.table{display:grid;gap:6px}
.table .thead,.table .row{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:8px;align-items:center}
.table .thead{font-weight:600;color:#555}
.table .row{padding:8px;border:1px solid var(--border);border-radius:10px}
.actions .btn{margin-right:6px}
</style>
