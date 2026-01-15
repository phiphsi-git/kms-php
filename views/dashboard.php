<?php
use App\Auth;
use App\Policy;

$user = Auth::user();
$canEditTask = Policy::can('tasks.update');

// Erwartete Gruppen-Schlüssel aus TaskRepo::groupByDueBuckets():
// 'overdue', 'today', 'tomorrow', 'week'
$groups = is_array($groups ?? null) ? $groups : [];
$groups += ['overdue'=>[], 'today'=>[], 'tomorrow'=>[], 'week'=>[]];

function renderBucket(string $key, string $title, array $items, bool $canEditTask): void { ?>
  <div class="card">
    <h3><?= htmlspecialchars($title) ?> (<?= count($items) ?>)</h3>
    <?php if (!count($items)): ?>
      <p class="muted">
        <?php
          echo match ($key) {
            'overdue' => 'Keine überfälligen Aufgaben.',
            'today' => 'Keine Aufgaben für heute.',
            'tomorrow' => 'Keine Aufgaben für morgen.',
            'week' => 'Keine weiteren Aufgaben in dieser Woche.',
            default => 'Keine Aufgaben.',
          };
        ?>
      </p>
    <?php else: ?>
      <ul class="task-list">
        <?php foreach ($items as $t):
          // $t sollte mind. id, customer_id, title, due_date haben
          $href = $canEditTask
            ? '?route=task_edit&id='.(int)$t['id']
            : '?route=customer_view&id='.(int)$t['customer_id'];
          $dueStr = !empty($t['due_date']) ? date('d.m.Y H:i', strtotime($t['due_date'])) : null;
        ?>
          <li class="task-row">
            <a class="task-link <?= htmlspecialchars($key) ?>" href="<?= $href ?>">
              <div class="task-main">
                <strong><?= htmlspecialchars($t['title'] ?? '') ?></strong>
                <small class="muted">
                  <?= htmlspecialchars($t['customer_name'] ?? '') ?>
                  <?php if (!empty($t['system_name'])): ?>
                    · <?= htmlspecialchars($t['system_name']) ?>
                  <?php endif; ?>
                  <?php if ($dueStr): ?>
                    · Fällig: <?= htmlspecialchars($dueStr) ?>
                  <?php endif; ?>
                  <?php if (!empty($t['is_paused'])): ?>
                    · <span class="dot dot--red" title="Pausiert"></span>
                  <?php endif; ?>
                </small>
              </div>
              <div class="task-meta">
                <?php
                  $badgeClass = match ($key) {
                    'overdue' => 'danger',
                    'today'   => 'warning',
                    'tomorrow'=> '',
                    'week'    => 'neutral',
                    default   => '',
                  };
                ?>
                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($t['status'] ?? 'offen') ?></span>
              </div>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
<?php } ?>

<section class="dash">
  <header class="dash-header">
    <h2>Dashboard</h2>
    <div class="muted">Willkommen, <?= htmlspecialchars($user['email'] ?? '') ?></div>
  </header>

  <div class="grid-2 mt-4">
    <?php renderBucket('overdue',  'Überfällig',  $groups['overdue'],  $canEditTask); ?>
    <?php renderBucket('today',    'Heute',       $groups['today'],    $canEditTask); ?>
    <?php renderBucket('tomorrow', 'Morgen',      $groups['tomorrow'], $canEditTask); ?>
    <?php renderBucket('week',     'Diese Woche', $groups['week'],     $canEditTask); ?>
  </div>
</section>

<style>
/* klickbare Aufgabenzeilen */
.task-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:8px; }
.task-row { margin:0; padding:0; }
.task-link {
  display:flex; align-items:center; justify-content:space-between;
  padding:10px 12px; border:1px solid var(--border);
  border-radius:10px; background:#fff;
  text-decoration:none; color:inherit; transition:background .15s,border .15s;
}
.task-link:hover { background:#f8fafc; border-color:#dbeafe; }
.task-main strong { display:block; margin-bottom:2px; }
.task-main small { font-size:12px; color:#666; }
.task-meta .badge { font-size:11px; padding:2px 8px; border-radius:10px; border:1px solid #ccc; }

/* Farbakzente je Bucket */
.badge.danger { background:#fee2e2; border-color:#fecaca; }
.badge.warning{ background:#fff7da; border-color:#fce29f; }
.badge.neutral{ background:#eaf7ef; border-color:#c5e6d1; }
.task-link.overdue:hover  { background:#fff1f1; }
.task-link.today:hover    { background:#fffbea; }
.task-link.tomorrow:hover { background:#f0f9ff; }
</style>
