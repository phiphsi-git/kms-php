<?php
namespace App;

use PDO;

class DashboardRepo {

    public static function getStats(): array {
        $pdo = DB::pdo();
        
        // 1. KPIs
        $tasksOpen = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('offen', 'ausstehend')")->fetchColumn();
        $tasksOverdue = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('offen', 'ausstehend') AND due_date < CURDATE()")->fetchColumn();
        
        // Eigene Aufgaben
        $userId = \App\Auth::user()['id'] ?? 0;
        $myTasks = 0;
        if ($userId) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM tasks t 
                JOIN customers c ON c.id = t.customer_id 
                WHERE t.status IN ('offen', 'ausstehend') 
                AND (c.responsible_technician_id = ? OR c.owner_user_id = ?)
            ");
            $stmt->execute([$userId, $userId]);
            $myTasks = $stmt->fetchColumn();
        }

        // 4. Letzte globale Aktivitäten (Logs)
        // LIMIT ERHÖHT AUF 50 FÜR SCROLL-LISTE
        $logs = [];
        if (class_exists('\App\ChangeLogRepo')) {
            $sql = "SELECT l.*, u.email as user_email, c.name as customer_name 
                    FROM customer_change_log l
                    LEFT JOIN users u ON u.id = l.user_id
                    LEFT JOIN customers c ON c.id = l.customer_id
                    ORDER BY l.created_at DESC LIMIT 50";
            $logs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // 3. Top Burner
        $burners = $pdo->query("
            SELECT c.id, c.name, COUNT(t.id) as cnt 
            FROM customers c 
            JOIN tasks t ON t.customer_id = c.id 
            WHERE t.status IN ('offen', 'ausstehend') 
            GROUP BY c.id 
            ORDER BY cnt DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'open_total' => (int)$tasksOpen,
            'overdue'    => (int)$tasksOverdue,
            'my_open'    => (int)$myTasks,
            'logs'       => $logs,
            'burners'    => $burners
        ];
    }

    public static function globalSearch(string $term): array {
        $pdo = DB::pdo();
        $term = "%$term%";
        $results = [];

        $stmt = $pdo->prepare("SELECT id, name as title, 'customer' as type, city as info FROM customers WHERE name LIKE ? LIMIT 5");
        $stmt->execute([$term]);
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $results[] = $row;

        $stmt = $pdo->prepare("SELECT s.id, s.name as title, 'system' as type, c.name as info, c.id as cid FROM systems s JOIN customers c ON c.id = s.customer_id WHERE s.name LIKE ? LIMIT 5");
        $stmt->execute([$term]);
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $results[] = $row;

        $stmt = $pdo->prepare("SELECT t.id, t.title, 'task' as type, c.name as info, c.id as cid FROM tasks t JOIN customers c ON c.id = t.customer_id WHERE t.title LIKE ? LIMIT 5");
        $stmt->execute([$term]);
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $results[] = $row;

        return $results;
    }
}