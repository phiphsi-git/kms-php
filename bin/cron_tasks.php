<?php
if (php_sapi_name() !== 'cli') die("CLI only");
require_once __DIR__ . '/../src/Config.php'; \App\Config::load();
require_once __DIR__ . '/../src/DB.php'; require_once __DIR__ . '/../src/TaskRepo.php'; require_once __DIR__ . '/../src/TaskCheckpointRepo.php'; require_once __DIR__ . '/../src/ChangeLogRepo.php';
use App\DB; use App\TaskRepo; use App\TaskCheckpointRepo; use App\ChangeLogRepo;

echo "--- Cron Start: " . date('Y-m-d H:i:s') . " ---\n";
try {
    $pdo = DB::pdo();
    // Holen der Tasks plus Datum der letzten Erledigung
    $sql = "
        SELECT t.*, 
               c.maintenance_type, c.maintenance_time, c.maintenance_weekday, c.maintenance_week_of_month, c.maintenance_year_month, c.maintenance_year_day,
               (SELECT MAX(changed_at) FROM task_status_log WHERE task_id = t.id AND status = 'erledigt') as last_done
        FROM tasks t 
        JOIN customers c ON c.id = t.customer_id 
        WHERE t.status = 'erledigt' AND t.is_recurring = 1 AND t.is_paused = 0
    ";
    
    $tasks = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $count = 0;
    $today = new DateTimeImmutable('today', new DateTimeZone('Europe/Zurich'));

    foreach ($tasks as $t) {
        // CHECK 1: Wurde sie heute schon erledigt?
        if (!empty($t['last_done'])) {
            $lastDoneDate = new DateTimeImmutable($t['last_done'], new DateTimeZone('Europe/Zurich'));
            if ($lastDoneDate->format('Y-m-d') === $today->format('Y-m-d')) {
                // Heute erledigt -> Nichts tun, erst morgen wieder
                continue;
            }
        }

        // CHECK 2: Ist sie laut Plan heute (oder früher) fällig?
        // true = ignoriere Uhrzeit (findet den Termin von heute, auch wenn es schon später ist)
        $next = TaskRepo::nextFromCustomer($t, true); 
        
        if (!$next) continue;
        
        if ($next->setTime(0,0,0) <= $today->setTime(0,0,0)) {
            // Reopen
            $pdo->prepare("UPDATE tasks SET status='offen', due_date=? WHERE id=?")->execute([$next->format('Y-m-d H:i:s'), $t['id']]);
            TaskCheckpointRepo::resetByTask((int)$t['id']);
            if (class_exists('\App\ChangeLogRepo')) ChangeLogRepo::log((int)$t['customer_id'], 'task', 'auto-reopen', "Aufgabe '{$t['title']}' automatisch wiedereröffnet.");
            echo "Updated Task #{$t['id']} ({$t['title']})\n"; 
            $count++;
        }
    }
    echo "--- Fertig. $count Updated. ---\n";
} catch (Exception $e) { echo "[Error] " . $e->getMessage() . "\n"; }