<?php use App\Csrf; ?>
<section class="dash">
  <header class="dash-header">
    <div><h2>Neues System anlegen</h2></div>
    <div class="actions"><a class="btn" href="?route=customer_view&id=<?= (int)$customerId ?>">Zurück</a></div>
  </header>

  <?php if (!empty($errors)): ?>
    <div class="alert error"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
  <?php endif; ?>

  <form method="post" action="?route=system_create" class="form">
    <?= Csrf::field() ?>
    <input type="hidden" name="customer_id" value="<?= (int)$customerId ?>">

    <label>Systemname* <input type="text" name="name" required></label>
    <div class="grid">
      <label>Typ <input type="text" name="type" placeholder="z. B. Windows Server 2022"></label>
      <label>Rolle <input type="text" name="role" placeholder="z. B. Applikationsserver"></label>
    </div>
    <div class="grid">
      <label>Version <input type="text" name="version"></label>
      <label>Installationsdatum <input type="date" name="install_date"></label>
    </div>

    <label>Verantwortlicher Techniker
      <select name="responsible_technician_id">
        <option value="">– auswählen –</option>
        <?php foreach ($technicians as $t): ?>
          <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['email']) ?> (<?= htmlspecialchars($t['role']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Notizen
      <textarea name="notes" rows="4"></textarea>
    </label>

    <button class="btn primary" type="submit">Speichern</button>
  </form>
</section>
