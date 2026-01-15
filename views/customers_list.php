<?php
// Schutz: falls Controller mal nichts liefert
$customers = is_array($customers) ? $customers : [];
?>
<section class="dash">
  <header class="dash-header">
    <div>
      <h2>Kunden</h2>
      <p class="muted">Übersicht aller Kunden mit Verantwortlichen & Fälligkeiten</p>
    </div>
	<form method="get" action="" class="filters" style="margin-top:8px">
  <input type="hidden" name="route" value="customers">
  <input type="search" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Suche nach Name/Techniker …" />
  <select name="sort">
    <option value="name_asc"   <?= ($sort??'')==='name_asc'?'selected':'' ?>>Name ↑</option>
    <option value="name_desc"  <?= ($sort??'')==='name_desc'?'selected':'' ?>>Name ↓</option>
    <option value="next_due_asc"  <?= ($sort??'')==='next_due_asc'?'selected':'' ?>>Fälligkeit ↑</option>
    <option value="next_due_desc" <?= ($sort??'')==='next_due_desc'?'selected':'' ?>>Fälligkeit ↓</option>
    <option value="systems_desc"  <?= ($sort??'')==='systems_desc'?'selected':'' ?>>Systeme ↓</option>
  </select>
  <button class="btn" type="submit">Anwenden</button>
</form>
    <div class="actions">
      <?php if (\App\Policy::can('customers.create')): ?>
        <a class="btn primary" href="?route=customer_new">+ Neuer Kunde</a>
      <?php endif; ?>
    </div>
  </header>

  <div class="cards">
    <?php if (empty($customers)): ?>
      <div class="card"><strong>Noch keine Kunden</strong><br><span class="muted">Lege den ersten Kunden an.</span></div>
    <?php endif; ?>

    <?php foreach ($customers as $c): ?>
      <article class="card customer-card">
        <div class="customer-header">
          <img src="<?= htmlspecialchars($c['logo_url'] ?: 'https://via.placeholder.com/64?text=K') ?>" alt="" class="avatar">
          <div>
            <h3 style="margin:0"><?= htmlspecialchars($c['name']) ?></h3>
            <div class="muted">
              <?php if (!empty($c['website'])): ?>
                <a href="<?= htmlspecialchars($c['website']) ?>" target="_blank" rel="noopener">Webseite</a> ·
              <?php endif; ?>
              Techniker: <?= htmlspecialchars($c['technician_email'] ?? '–') ?>
            </div>
          </div>
        </div>

        <dl class="stats">
          <div><dt>Nächstes Fällig</dt><dd><?= $c['next_due'] ?: '–' ?></dd></div>
          <div><dt>Systeme</dt><dd><?= (int)$c['systems_count'] ?></dd></div>
          <div><dt>Aufgaben offen</dt><dd><?= (int)$c['tasks_open'] ?></dd></div>
          <div><dt>Aufgaben erledigt</dt><dd><?= (int)$c['tasks_done'] ?></dd></div>
          <div><dt>Ausstehend</dt><dd><?= (int)$c['tasks_pending'] ?></dd></div>
        </dl>

        <div class="card-actions">
          <a class="btn" href="?route=customer_view&id=<?= (int)$c['id'] ?>">Öffnen</a>
          <?php if (\App\Policy::can('customers.update')): ?>
            <a class="btn" href="?route=customer_edit&id=<?= (int)$c['id'] ?>">Bearbeiten</a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
