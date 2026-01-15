<?php
$tz = new DateTimeZone('Europe/Zurich');
$start = $start ?? new DateTimeImmutable('monday this week', $tz);
$end   = $end   ?? new DateTimeImmutable('sunday this week 23:59:59', $tz);
$byDay = [];
$cursor = $start;
while ($cursor <= $end) { $byDay[$cursor->format('Y-m-d')] = []; $cursor = $cursor->modify('+1 day'); }
foreach ($events as $e) { $byDay[ (new DateTimeImmutable($e['due_date'], $tz))->format('Y-m-d') ][] = $e; }
?>
<section class="card">
  <div class="card-head-between">
    <h3>Wartungs-Kalender (KW <?= (int)$start->format('W') ?>)</h3>
    <div class="actions">
      <a class="btn" href="?route=calendar&start=<?= urlencode($start->modify('-7 day')->format('Y-m-d')) ?>&end=<?= urlencode($end->modify('-7 day')->format('Y-m-d H:i:s')) ?>">« Vorherige</a>
      <a class="btn" href="?route=calendar&start=<?= urlencode((new DateTimeImmutable('monday this week', $tz))->format('Y-m-d')) ?>&end=<?= urlencode((new DateTimeImmutable('sunday this week 23:59:59', $tz))->format('Y-m-d H:i:s')) ?>">Heute</a>
      <a class="btn" href="?route=calendar&start=<?= urlencode($start->modify('+7 day')->format('Y-m-d')) ?>&end=<?= urlencode($end->modify('+7 day')->format('Y-m-d H:i:s')) ?>">Nächste »</a>
    </div>
  </div>

  <div class="week-grid">
    <?php foreach ($byDay as $day => $list): ?>
      <div class="day">
        <div class="day-head"><?= htmlspecialchars(strftime('%A, %d.%m.', strtotime($day))) ?></div>
        <?php if (!$list): ?>
          <div class="muted">—</div>
        <?php else: ?>
          <ul class="task-list">
            <?php foreach ($list as $t): 
              $href = \App\Policy::can('tasks.update') ? '?route=task_edit&id='.(int)$t['id'] : '?route=customer_view&id='.(int)$t['customer_id'];
            ?>
              <li class="task-row">
                <a class="task-link" href="<?= $href ?>">
                  <div class="task-main">
                    <strong><?= htmlspecialchars($t['title']) ?></strong>
                    <small class="muted"><?= date('H:i', strtotime($t['due_date'])) ?> · <?= htmlspecialchars($t['customer_name']) ?><?php if(!empty($t['system_name'])): ?> · <?= htmlspecialchars($t['system_name']) ?><?php endif; ?></small>
                  </div>
                  <div class="task-meta"><span class="badge"><?= htmlspecialchars($t['status']) ?></span></div>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<style>
.week-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:12px}
.day{border:1px solid var(--border);border-radius:10px;padding:8px;background:#fff}
.day-head{font-weight:600;margin-bottom:6px}
.task-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:6px}
.task-link{display:flex;justify-content:space-between;gap:12px;padding:8px;border:1px solid var(--border);border-radius:8px;text-decoration:none;color:inherit;background:#fafafa}
.task-link:hover{background:#f8fafc;border-color:#dbeafe}
.badge{border:1px solid #ccc;border-radius:10px;padding:2px 8px;font-size:11px}
</style>
