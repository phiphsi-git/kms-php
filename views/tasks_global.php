<?php
// Filter Parameter
$filter = $_GET['filter'] ?? 'all';
$userId = \App\Auth::user()['id'];

// Aufgaben laden
$tasks = \App\TaskRepo::listGlobal($filter, $userId);

// Überschrift und Beschreibung je nach Filter
$pageTitle = 'Aufgaben';
$subTitle  = 'Gesamtübersicht aller offenen Punkte';

switch($filter) {
    case 'overdue': 
        $pageTitle = 'Überfällige Aufgaben'; 
        $subTitle  = 'Diese Aufgaben erfordern sofortige Aufmerksamkeit';
        break;
    case 'mine': 
        $pageTitle = 'Meine Aufgaben'; 
        $subTitle  = 'Aufgaben meiner zugeordneten Kunden';
        break;
}
?>

<style>
.dash { max-width: 1000px; margin: 0 auto; padding: 20px; font-family: -apple-system, sans-serif; color:#333; }
.dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
.dash-title h2 { margin: 0 0 5px 0; font-size: 1.8rem; color: #222; }
.muted { color: #777; font-size: 0.95rem; }
.btn-icon { display: inline-flex; align-items: center; gap: 6px; background: #fff; border: 1px solid #ccc; border-radius: 6px; color: #555; padding: 8px 12px; text-decoration: none; font-size: 0.9rem; transition: all 0.2s ease; }
.btn-icon:hover { background: #f5f5f5; border-color: #bbb; color: #333; }
.content-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
.list-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #f0f0f0; transition: background 0.1s; }
.list-item:last-child { border-bottom: none; }
.list-item:hover { background: #fafafa; }
.task-meta { font-size: 0.85rem; color: #666; margin-top: 4px; display:flex; gap:10px; align-items:center; }
.task-meta a { color: #555; text-decoration: none; font-weight: 500; }
.task-meta a:hover { text-decoration: underline; color: #0056b3; }
.badge { padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.badge-overdue { background: #ffe6e6; color: #d9534f; }
.badge-today { background: #fff7e6; color: #e67e22; }
.badge-future { background: #e3f2fd; color: #0275d8; }
.status-dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
</style>

<section class="dash">
    
    <header class="dash-header">
        <div class="dash-title">
            <h2><?= htmlspecialchars($pageTitle) ?></h2>
            <div class="muted"><?= count($tasks) ?> Einträge gefunden · <?= htmlspecialchars($subTitle) ?></div>
        </div>
        <div class="actions">
            <a href="?route=dashboard" class="btn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Zurück zum Dashboard
            </a>
        </div>
    </header>

    <div class="content-card">
        <?php if(empty($tasks)): ?>
            <div style="padding:50px; text-align:center; color:#888;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" width="48" height="48" style="color:#ddd; margin-bottom:10px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <h3 style="margin:0; font-weight:normal;">Alles erledigt!</h3>
                <p>Keine Aufgaben in dieser Ansicht.</p>
            </div>
        <?php else: ?>
            <?php foreach($tasks as $t): 
                $due = !empty($t['due_date']) ? strtotime($t['due_date']) : null;
                $todayEnd = strtotime('today 23:59:59');
                
                // Badge Logik
                $dateBadge = '';
                if ($due) {
                    if ($due < time()) {
                        $dateBadge = '<span class="badge badge-overdue">Überfällig: '.date('d.m.', $due).'</span>';
                    } elseif ($due <= $todayEnd) {
                        $dateBadge = '<span class="badge badge-today">Heute: '.date('H:i', $due).'</span>';
                    } else {
                        $dateBadge = '<span class="badge badge-future">'.date('d.m.', $due).'</span>';
                    }
                }

                // Status Farbe
                $statusColor = match($t['status']) { 'offen'=>'#d9534f', 'ausstehend'=>'#f0ad4e', default=>'#28a745' };
            ?>
            <div class="list-item">
                <div style="flex:1;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                        <span class="status-dot" style="background:<?= $statusColor ?>" title="<?= ucfirst($t['status']) ?>"></span>
                        <strong style="font-size:1.05rem; color:#333;">
                            <a href="?route=task_edit&id=<?= $t['id'] ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($t['title']) ?></a>
                        </strong>
                        <?= $dateBadge ?>
                    </div>
                    
                    <div class="task-meta">
                        <a href="?route=customer_view&id=<?= $t['customer_id'] ?>" title="Zum Kunden">
                            🏢 <?= htmlspecialchars($t['customer_name']) ?>
                        </a>
                        
                        <?php if(!empty($t['system_name'])): ?>
                            <span style="color:#ccc;">|</span>
                            <a href="?route=system_edit&id=<?= $t['system_id'] ?>" title="Zum System">
                                💻 <?= htmlspecialchars($t['system_name']) ?>
                            </a>
                        <?php endif; ?>

                        <?php if(!empty($t['is_paused'])): ?>
                            <span style="color:#ccc;">|</span>
                            <span style="color:#f0ad4e;">⏸ Pausiert</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:flex; gap:10px; align-items:center;">
                    <a href="?route=task_edit&id=<?= $t['id'] ?>" class="btn-icon" style="padding:6px 10px;" title="Bearbeiten">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>