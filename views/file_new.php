<?php use App\Csrf; ?>
<section class="dash">
  <header class="dash-header">
    <div><h2>Datei hochladen</h2></div>
    <div class="actions"><a class="btn" href="?route=customer_view&id=<?= (int)$customerId ?>">Zurück</a></div>
  </header>

  <?php if (!empty($errors)): ?>
    <div class="alert error"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
  <?php endif; ?>

  <form method="post" action="?route=file_create" enctype="multipart/form-data" class="form">
    <?= Csrf::field() ?>
    <input type="hidden" name="customer_id" value="<?= (int)$customerId ?>">

    <label>Datei auswählen* <input type="file" name="file" required></label>

    <label>Kurzbeschreibung
      <input type="text" name="description" maxlength="500" placeholder="z. B. ‚Backup-Konzept 2025‘">
    </label>

<div class="grid">
  <label>Systeme (optional, Mehrfach)
    <select name="system_ids[]" multiple size="6">
      <?php foreach ($systems as $s): ?>
        <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Aufgaben (optional, Mehrfach)
    <select name="task_ids[]" multiple size="6">
      <?php foreach ($tasks as $t): ?>
        <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
</div>


    <button class="btn primary" type="submit">Hochladen</button>
  </form>
</section>
