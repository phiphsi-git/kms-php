<?php use App\Csrf; ?>
<section class="dash">
  <header class="dash-header">
    <div><h2>Aufgabe bearbeiten</h2></div>
    <div class="actions"><a class="btn" href="?route=customer_view&id=<?= (int)$t['customer_id'] ?>">Zurück</a></div>
  </header>

	<?php if (!empty($templates)): ?>
	  <label>Vorlage
		<select name="template_id" onchange="applyTemplate(this.value)">
		  <option value="">— wählen —</option>
		  <?php foreach ($templates as $tpl): ?>
			<option value="<?= (int)$tpl['id'] ?>"
			  data-title="<?= htmlspecialchars($tpl['title']) ?>"
			  data-comment="<?= htmlspecialchars($tpl['default_comment'] ?? '') ?>"
			  data-rec="<?= (int)$tpl['is_recurring'] ?>"
			><?= htmlspecialchars($tpl['title']) ?></option>
		  <?php endforeach; ?>
		</select>
	  </label>
	  <script>
		async function applyTemplate(id){
		  if(!id){return;}
		  try{
			const res = await fetch('?route=template_load&id='+id);
			const data = await res.json();
			if(data && data.ok){
			  if(data.tpl){
				if(document.querySelector('input[name=title]') && !document.querySelector('input[name=title]').value)
				  document.querySelector('input[name=title]').value = data.tpl.title || '';
				if(document.querySelector('textarea[name=comment]') && !document.querySelector('textarea[name=comment]').value)
				  document.querySelector('textarea[name=comment]').value = data.tpl.default_comment || '';
				if(document.querySelector('input[name=is_recurring]'))
				  document.querySelector('input[name=is_recurring]').checked = !!(+data.tpl.is_recurring);
			  }
			  if(data.checkpoints && window.addCpRow){
				data.checkpoints.forEach((cp, idx)=>{
				  addCpRow();
				  const rows = document.querySelectorAll('#cpRows .cp-row');
				  const row = rows[rows.length-1];
				  row.querySelector('input[name="cp_label[]"]').value = cp.label || '';
				  const chk = row.querySelector('input[type=checkbox][name^="cp_reqcmt"]');
				  if(chk) chk.checked = !!(+cp.require_comment_on_fail);
				});
			  }
			}
		  }catch(e){ console.error(e); }
		}
	  </script>
	<?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert error"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
  <?php endif; ?>

  <form method="post" action="?route=task_update&id=<?= (int)$t['id'] ?>" class="form">
    <?= Csrf::field() ?>
    <label>Titel* <input type="text" name="title" required value="<?= htmlspecialchars($t['title']) ?>"></label>

    <div class="grid">
      <label>System
        <select name="system_id">
          <option value="">– keines –</option>
          <?php foreach ($systems as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= ((int)$t['system_id'] === (int)$s['id'])?'selected':'' ?>>
              <?= htmlspecialchars($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
	  
      <label>Status
        <select name="status">
          <?php $st = $t['status']; ?>
          <option value="offen"      <?= $st==='offen'?'selected':'' ?>>offen</option>
          <option value="ausstehend" <?= $st==='ausstehend'?'selected':'' ?>>ausstehend</option>
          <option value="erledigt"   <?= $st==='erledigt'?'selected':'' ?>>erledigt</option>
        </select>
      </label>
    </div>

	<div class="grid">
	  <label>
		<input type="checkbox" name="is_recurring" value="1" <?= !empty($t['is_recurring'])?'checked':'' ?>>
		Durch Kunden-Wartungsintervall steuern
	  </label>
	  <label>
		<input type="checkbox" name="is_paused" value="1" <?= !empty($t['is_paused'])?'checked':'' ?>>
		Aufgabe pausieren (Intervall greift nicht)
	  </label>
	</div>

	<label id="fld_pause_reason" style="<?= empty($t['is_paused'])?'display:none':'' ?>">Grund der Pausierung
	  <input type="text" name="pause_reason" maxlength="500" value="<?= htmlspecialchars($t['pause_reason'] ?? '') ?>">
	</label>

	<script>
	(function(){
	  const cb = document.querySelector('input[name="is_paused"]');
	  const fld = document.getElementById('fld_pause_reason');
	  function upd(){ fld.style.display = cb.checked ? '' : 'none'; }
	  cb.addEventListener('change', upd);
	})();
	</script>

    <label>Fälligkeitsdatum/Zeit
      <input type="datetime-local" name="due_date"
             value="<?= !empty($t['due_date']) ? date('Y-m-d\TH:i', strtotime($t['due_date'])) : '' ?>">
    </label>

    <label>Kommentar
      <textarea name="comment" rows="3"><?= htmlspecialchars($t['comment'] ?? '') ?></textarea>
    </label>
	
	<?php $files = \App\FileRepo::listByTask((int)$t['id']); ?>
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
	
	<?php if (!empty($t['id'])): 
	  $log = \App\TaskStatusLogRepo::listRecent((int)$t['id'], 10);
	?>
	  <div class="card" style="margin-top:12px">
		<h3>Letzte Änderungen</h3>
		<?php if (!$log): ?>
		  <p class="muted">Keine Einträge.</p>
		<?php else: ?>
		  <ul class="simple-list">
			<?php foreach ($log as $row): ?>
			  <li>
				<strong><?= htmlspecialchars($row['status']) ?></strong>
				· <?= htmlspecialchars($row['user_email'] ?? '—') ?>
				· <small class="muted"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($row['changed_at']))) ?></small>
				<?php if (!empty($row['comment'])): ?><div class="muted"><?= nl2br(htmlspecialchars($row['comment'])) ?></div><?php endif; ?>
			  </li>
			<?php endforeach; ?>
		  </ul>
		<?php endif; ?>
	  </div>
	<?php endif; ?>

    <div class="flex gap">
      <button class="btn primary" type="submit">Speichern</button>
      <form method="post" action="?route=task_delete&id=<?= (int)$t['id'] ?>" onsubmit="return confirm('Aufgabe wirklich löschen?');" style="display:inline">
        <?= Csrf::field() ?>
        <button class="btn danger" type="submit">Löschen</button>
      </form>
    </div>
	
	<?php
	// vorhandene Punkte laden, wenn nicht aus Postback vorhanden
	if (!isset($checkpoints)) {
	  $rows = \App\TaskCheckpointRepo::listByTask((int)$t['id']);
	  $cp_ids=$cp_labels=$cp_done=$cp_req=$cp_cmts=$cp_orders=[];
	  foreach ($rows as $i=>$r) {
		$cp_ids[]   = (int)$r['id'];
		$cp_labels[] = $r['label'];
		$cp_done[]   = (int)$r['is_done'];
		$cp_req[]    = (int)$r['require_comment_on_fail'];
		$cp_cmts[]   = $r['comment'];
		$cp_orders[] = (int)$r['sort_order'];
	  }
	} else {
	  // aus Postback (Fehlerfall)
	  $cp_ids   = $checkpoints['ids'] ?? [];
	  $cp_labels= $checkpoints['labels'] ?? [];
	  $cp_done  = $checkpoints['is_done'] ?? [];
	  $cp_req   = $checkpoints['require_comment_on_fail'] ?? [];
	  $cp_cmts  = $checkpoints['comments'] ?? [];
	  $cp_orders= $checkpoints['orders'] ?? [];
	}
	?>

	<div class="card" style="margin-top:12px">
	  <div class="card-head-between">
		<h3>Kontrollpunkte</h3>
		<button type="button" class="icon-btn" title="Neuer Kontrollpunkt" onclick="addCpRow()">
		  <svg class="icon"><use href="#i-plus"/></svg>
		</button>
	  </div>

	  <div id="cpRows" class="cp-grid">
		<?php
		  $count = max(count($cp_labels), 1);
		  for ($i=0; $i<$count; $i++):
			$id   = htmlspecialchars((string)($cp_ids[$i] ?? ''));
			$txt  = htmlspecialchars((string)($cp_labels[$i] ?? ''));
			$done = !empty($cp_done[$i]);
			$req  = isset($cp_req[$i]) ? (int)$cp_req[$i] : 1;
			$cmt  = htmlspecialchars((string)($cp_cmts[$i] ?? ''));
			$ord  = htmlspecialchars((string)($cp_orders[$i] ?? $i));
		?>
		  <div class="cp-row">
			<input type="hidden" name="cp_id[]" value="<?= $id ?>">
			<input type="hidden" name="cp_order[]" value="<?= $ord ?>" class="cp-order">

			<!-- OK -->
			<label class="cp-ok" title="OK">
			  <input type="checkbox" name="cp_done[<?= $i ?>]" value="1" <?= $done?'checked':'' ?>>
			</label>

			<!-- Text -->
			<input class="cp-text" type="text" name="cp_label[]" value="<?= $txt ?>" placeholder="Kontrollpunkt…" required>

			<!-- Kommentar -->
			<input class="cp-comment" type="text" name="cp_comment[]" value="<?= $cmt ?>" placeholder="Kommentar (falls nicht ok)">

			<!-- Bemerkungspflicht -->
			<label class="cp-toggle" title="Bemerkungspflicht bei »nicht ok«">
			  <input type="checkbox" name="cp_reqcmt[<?= $i ?>]" value="1" <?= $req ? 'checked':'' ?>>
			  <svg class="icon"><use href="#i-require"/></svg>
			</label>

			<!-- Entfernen -->
			<button type="button" class="icon-btn danger" title="Entfernen" onclick="removeCpRow(this)">
			  <svg class="icon"><use href="#i-trash"/></svg>
			</button>
		  </div>
		<?php endfor; ?>
	  </div>

	  <small class="muted">Kommentar wird nur verlangt, wenn das Häkchen nicht gesetzt ist und die Bemerkungspflicht aktiv ist.</small>
	</div>

	<script>
	function addCpRow(){
	  const cont = document.getElementById('cpRows');
	  const idx  = cont.querySelectorAll('.cp-row').length;
	  const row = document.createElement('div');
	  row.className = 'cp-row';
	  row.innerHTML = `
		<input type="hidden" name="cp_id[]" value="">
		<input type="hidden" name="cp_order[]" value="${idx}" class="cp-order">

		<label class="cp-ok" title="OK">
		  <input type="checkbox" name="cp_done[${idx}]" value="1">
		</label>

		<input class="cp-text" type="text" name="cp_label[]" placeholder="Kontrollpunkt…" required>

		<input class="cp-comment" type="text" name="cp_comment[]" placeholder="Kommentar (falls nicht ok)">

		<label class="cp-toggle" title="Bemerkungspflicht bei »nicht ok«">
		  <input type="checkbox" name="cp_reqcmt[${idx}]" value="1" checked>
		  <svg class="icon"><use href="#i-require"/></svg>
		</label>

		<button type="button" class="icon-btn danger" title="Entfernen" onclick="removeCpRow(this)">
		  <svg class="icon"><use href="#i-trash"/></svg>
		</button>
	  `;
	  cont.appendChild(row);
	}

	function removeCpRow(btn){
	  const row = btn.closest('.cp-row'); if (row) row.remove();
	}
	</script>


  </form>
</section>
