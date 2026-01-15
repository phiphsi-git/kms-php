<?php use App\Csrf; ?>
<section class="dash">
  <header class="dash-header">
    <div><h2>Neue Aufgabe anlegen</h2></div>
    <div class="actions"><a class="btn" href="?route=customer_view&id=<?= (int)$customerId ?>">Zurück</a></div>
  </header>

  <?php if (!empty($errors)): ?>
    <div class="alert error"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
  <?php endif; ?>

  <form method="post" action="?route=task_create" class="form">
    <?= Csrf::field() ?>
    <input type="hidden" name="customer_id" value="<?= (int)$customerId ?>">

    <label>Titel* <input type="text" name="title" required></label>

    <div class="grid">
      <label>System (optional)
        <select name="system_id">
          <option value="">– keines –</option>
          <?php foreach ($systems as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= isset($systemId)&&$systemId==$s['id']?'selected':'' ?>>
              <?= htmlspecialchars($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Status
        <select name="status">
          <option value="offen">offen</option>
          <option value="ausstehend">ausstehend</option>
          <option value="erledigt">erledigt</option>
        </select>
      </label>
    </div>

	<div class="grid">
	  <label>
		<input type="checkbox" name="is_recurring" value="1" checked>
		Durch Kunden-Wartungsintervall steuern
	  </label>
	  <label>
		<input type="checkbox" name="is_paused" value="1">
		Aufgabe pausieren (Intervall greift nicht)
	  </label>
	</div>

	<label id="fld_pause_reason" style="display:none">Grund der Pausierung
	  <input type="text" name="pause_reason" maxlength="500">
	</label>

	<script>
	(function(){
	  const cb = document.querySelector('input[name="is_paused"]');
	  const fld = document.getElementById('fld_pause_reason');
	  function upd(){ fld.style.display = cb.checked ? '' : 'none'; }
	  cb.addEventListener('change', upd); upd();
	})();
	</script>

    <label>Fälligkeitsdatum/Zeit
      <input type="datetime-local" name="due_date">
    </label>

    <label>Kommentar
      <textarea name="comment" rows="3" placeholder="Begründung falls nicht erfolgreich / Hinweise"></textarea>
    </label>

    <button class="btn primary" type="submit">Speichern</button>
  </form>
</section>
