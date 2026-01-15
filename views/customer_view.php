<?php
use App\Csrf;
use App\Policy;

// Daten laden
$customerId = (int)$c['id'];
$systems = \App\SystemRepo::listByCustomer($customerId);
$tasks   = \App\TaskRepo::listByCustomer($customerId);
$files   = \App\FileRepo::listByCustomer($customerId);

// (optional) Reports-Archiv, falls vorhanden
$reports = [];
if (class_exists('\App\ReportRepo')) {
  $reports = \App\ReportRepo::listByCustomer($customerId);
}

// Zähler für Aufgaben
$cnt = ['offen'=>0,'ausstehend'=>0,'erledigt'=>0];
foreach ($tasks as $t) {
  $st = $t['status'] ?? '';
  if (isset($cnt[$st])) $cnt[$st]++;
}
?>
<section class="dash">

  <!-- Kopfzeile mit Aktionen -->
  <header class="dash-header">
    <div>
      <h2><?= htmlspecialchars($c['name']) ?></h2>
      <div class="muted">
        <?php if (!empty($c['street'])): ?>
          <?= htmlspecialchars($c['street']) ?>,
        <?php endif; ?>
        <?php if (!empty($c['zip']) || !empty($c['city'])): ?>
          <?= htmlspecialchars(($c['zip'] ?? '').' '.($c['city'] ?? '')) ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="actions">
      <a class="btn" href="?route=customers">Zurück</a>

      <?php if (Policy::can('systems.create')): ?>
        <a class="btn" href="?route=system_new&customer_id=<?= $customerId ?>">+ System</a>
      <?php endif; ?>

      <?php if (Policy::can('tasks.create')): ?>
        <a class="btn" href="?route=task_new&customer_id=<?= $customerId ?>">+ Aufgabe</a>
      <?php endif; ?>

      <?php if (Policy::can('files.upload')): ?>
        <a class="btn" href="?route=file_new&customer_id=<?= $customerId ?>">+ Datei</a>
      <?php endif; ?>

	  <?php if (class_exists('\App\ReportRepo') && \App\Policy::can('customers.view')): ?>
	    <a class="btn" href="?route=report_form&customer_id=<?= (int)$customerId ?>">PDF-Report</a>
	  <?php endif; ?>

      <?php if (Policy::can('customers.update')): ?>
        <a class="btn" href="?route=customer_edit&id=<?= $customerId ?>">Kunde bearbeiten</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- Zusammenfassung -->
  <div class="cards mt-3">
    <div class="card">
      <h3>Systeme</h3>
      <p>Gesamt: <strong><?= (int)($c['systems_count'] ?? count($systems)) ?></strong></p>
    </div>
    <div class="card">
      <h3>Aufgaben</h3>
      <ul>
        <li>Offen: <strong><?= (int)$cnt['offen'] ?></strong></li>
        <li>Ausstehend: <strong><?= (int)$cnt['ausstehend'] ?></strong></li>
        <li>Erledigt: <strong><?= (int)$cnt['erledigt'] ?></strong></li>
      </ul>
    </div>
  </div>

  <!-- Systeme & Aufgaben -->
  <section class="grid-2 mt-4">

    <!-- Systeme -->
    <div class="card">
      <div class="card-head-between">
        <h3>Systeme (<?= count($systems) ?>)</h3>
      </div>

      <?php if (empty($systems)): ?>
        <p class="muted">Keine Systeme erfasst.</p>
      <?php else: ?>
        <ul class="simple-list">
          <?php foreach ($systems as $s): ?>
            <li>
              <div>
                <strong><?= htmlspecialchars($s['name']) ?></strong><br>
                <small class="muted">
                  <?= htmlspecialchars($s['type'] ?? '') ?>
                  <?php if (!empty($s['role'])): ?> · <?= htmlspecialchars($s['role']) ?><?php endif; ?>
                  <?php if (!empty($s['version'])): ?> · v<?= htmlspecialchars($s['version']) ?><?php endif; ?>
                </small>
              </div>
              <?php if (Policy::can('systems.update')): ?>
                <div class="actions">
                  <!-- Bearbeiten -->
                  <a class="icon-btn" title="Bearbeiten" href="?route=system_edit&id=<?= (int)$s['id'] ?>">
                    <svg class="icon"><use href="#i-edit"/></svg>
                  </a>
                  <!-- Löschen -->
                  <form method="post" action="?route=system_delete&id=<?= (int)$s['id'] ?>"
                        onsubmit="return confirm('System wirklich löschen?');" style="display:inline">
                    <?= Csrf::field() ?>
                    <button class="icon-btn danger" type="submit" title="Löschen">
                      <svg class="icon"><use href="#i-trash"/></svg>
                    </button>
                  </form>
                </div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <!-- Aufgaben -->
    <div class="card">
      <div class="card-head-between">
        <h3>Aufgaben (<?= count($tasks) ?>)</h3>
      </div>

      <?php if (empty($tasks)): ?>
        <p class="muted">Keine Aufgaben erfasst.</p>
      <?php else: ?>
        <ul class="simple-list">
          <?php foreach ($tasks as $t): ?>
            <li>
              <div>
                <strong><?= htmlspecialchars($t['title']) ?></strong><br>
                <small class="muted">
                  Status: <?= htmlspecialchars($t['status']) ?>
                  <?php if (!empty($t['system_name'])): ?> · <?= htmlspecialchars($t['system_name']) ?><?php endif; ?>
                  <?php if (!empty($t['due_date'])): ?> · Fällig: <?= htmlspecialchars(date('d.m.Y H:i', strtotime($t['due_date']))) ?><?php endif; ?>
                  <?php if (!empty($t['is_paused'])): ?> · <span class="dot dot--red" title="Pausiert"></span><?php endif; ?>
                </small>
              </div>
              <?php if (Policy::can('tasks.update')): ?>
                <div class="actions">
                  <!-- Bearbeiten -->
                  <a class="icon-btn" title="Bearbeiten" href="?route=task_edit&id=<?= (int)$t['id'] ?>">
                    <svg class="icon"><use href="#i-edit"/></svg>
                  </a>
                  <!-- Löschen -->
                  <form method="post" action="?route=task_delete&id=<?= (int)$t['id'] ?>"
                        onsubmit="return confirm('Aufgabe wirklich löschen?');" style="display:inline">
                    <?= Csrf::field() ?>
                    <button class="icon-btn danger" type="submit" title="Löschen">
                      <svg class="icon"><use href="#i-trash"/></svg>
                    </button>
                  </form>
                </div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

  </section>

  <!-- Dateien (Datenablage) -->
  <div class="card mt-4">
    <div class="card-head-between">
      <h3>Datenablage (<?= count($files) ?>)</h3>
      <?php if (Policy::can('files.upload')): ?>
        <a class="btn" href="?route=file_new&customer_id=<?= $customerId ?>">+ Datei</a>
      <?php endif; ?>
    </div>

    <?php if (!$files): ?>
      <p class="muted">Noch keine Dateien.</p>
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
              <!-- Öffnen (inline; farbig) -->
              <a class="icon-btn icon-btn--open" title="Öffnen" href="?route=file_preview&id=<?= (int)$f['id'] ?>">
                <svg class="icon"><use href="#i-open"/></svg>
              </a>
              <!-- Download -->
              <a class="icon-btn" title="Download" href="?route=file_download&id=<?= (int)$f['id'] ?>">
                <svg class="icon"><use href="#i-download"/></svg>
              </a>
              <!-- Löschen (nur Admin/Projektleiter) -->
              <?php if (Policy::can('files.delete')): ?>
                <form method="post" action="?route=file_delete&id=<?= (int)$f['id'] ?>"
                      onsubmit="return confirm('Datei wirklich löschen?');" style="display:inline">
                  <?= Csrf::field() ?>
                  <button class="icon-btn danger" type="submit" title="Löschen">
                    <svg class="icon"><use href="#i-trash"/></svg>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <!-- Report-Archiv (falls aktiv) -->
  <?php if (!empty($reports)): ?>
    <div class="card mt-4">
      <div class="card-head-between">
        <h3>Report-Archiv (<?= count($reports) ?>)</h3>
      </div>
      <ul class="simple-list">
        <?php foreach ($reports as $r): ?>
          <li>
            <div>
              <strong><?= htmlspecialchars($r['title'] ?? 'Report') ?></strong>
              <small class="muted"> · <?= htmlspecialchars($r['filename']) ?> · <?= htmlspecialchars($r['created_at']) ?></small>
            </div>
            <div class="actions">
              <!-- Download -->
              <a class="icon-btn" title="Download" href="?route=report_download&id=<?= (int)$r['id'] ?>">
                <svg class="icon"><use href="#i-download"/></svg>
              </a>
              <!-- Löschen (nur Admin/Projektleiter via customers.update) -->
              <?php if (Policy::can('customers.update')): ?>
                <form method="post" action="?route=report_delete&id=<?= (int)$r['id'] ?>"
                      onsubmit="return confirm('Report löschen?');" style="display:inline">
                  <?= Csrf::field() ?>
                  <button class="icon-btn danger" type="submit" title="Löschen">
                    <svg class="icon"><use href="#i-trash"/></svg>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

	<?php if (\App\Policy::can('customers.delete')): ?>
	  <form method="post" action="?route=customer_delete&id=<?= (int)$c['id'] ?>"
			onsubmit="return confirm('Kunde „<?= htmlspecialchars($c['name']) ?>“ wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.');"
			style="display:inline">
		<?= \App\Csrf::field() ?>
		<button class="icon-btn danger" type="submit" title="Kunde löschen">
		  <svg class="icon"><use href="#i-trash"/></svg>
		</button>
	  </form>
	<?php endif; ?>

</section>
