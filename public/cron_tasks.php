<?php
// Skript läuft auf der Kommandozeile (CLI)
if (php_sapi_name() !== 'cli') {
    die("Nur für CLI-Zugriff erlaubt.");
}

// 1. Umgebung laden
require_once __DIR__ . '/../src/Config.php';
\App\Config::load(); 

require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/TaskRepo.php';
require_once __DIR__ . '/../src/TaskCheckpointRepo.php'; 
require_once __DIR__ . '/../src/ChangeLogRepo.php';

use App\DB;
use App\TaskRepo;
use App\TaskCheckpointRepo;
use App\ChangeLogRepo;

echo "--- KMS Cronjob Start: " . date('Y-m-d H:i:s') . " ---\n";

try {
    $pdo = DB::pdo();

    // 2. Alle erledigten, wiederkehrenden Aufgaben laden, die nicht pausiert sind
    $sql = "
        SELECT t.*, 
               c.maintenance_type, c.maintenance_time, c.maintenance_weekday,
               c.maintenance_week_of_month, c.maintenance_year_month, c.maintenance_year_day
        FROM tasks t
        JOIN customers c ON c.id = t.customer_id
        WHERE t.status = 'erledigt' 
          AND t.is_recurring = 1 
          AND t.is_paused = 0
    ";
    
    $tasks = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $count = 0;

    foreach ($tasks as $t) {
        // Nächste Fälligkeit berechnen
        $next = TaskRepo::nextFromCustomer($t);

        if (!$next) {
            echo "[Skip] Task #{$t['id']} ({$t['title']}): Kein Intervall berechenbar.\n";
            continue;
        }

        // Wenn das nächste Datum HEUTE (oder früher) ist -> Wiedereröffnen
        $today = new DateTimeImmutable('today', new DateTimeZone('Europe/Zurich'));
        $nextDate = $next->setTime(0,0,0);
        $todayDate = $today->setTime(0,0,0);

        if ($nextDate <= $todayDate) {
            // Task wieder öffnen und Fälligkeit setzen
            $updateSql = "UPDATE tasks SET status = 'offen', due_date = ? WHERE id = ?";
            $pdo->prepare($updateSql)->execute([$next->format('Y-m-d H:i:s'), $t['id']]);
            
            // Checkpoints zurücksetzen (Haken raus, Kommentare leeren)
            TaskCheckpointRepo::resetByTask((int)$t['id']);
            
            // Change Log Eintrag (Systemaktion)
            if (class_exists('\App\ChangeLogRepo')) {
                $cid = (int)$t['customer_id'];
                $msg = "Aufgabe '{$t['title']}' automatisch wiedereröffnet (Intervall).";
                ChangeLogRepo::log($cid, 'task', 'auto-reopen', $msg);
            }
            
            echo "[Update] Task #{$t['id']} ({$t['title']}) -> Offen für {$next->format('d.m.Y H:i')}\n";
            $count++;
        }
    }

    echo "--- Fertig. $count Aufgaben aktualisiert. ---\n";

} catch (Exception $e) {
    echo "[Error] " . $e->getMessage() . "\n";
    exit(1);
}