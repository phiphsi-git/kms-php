<?php
namespace App;

use PDO;

class ChangeLogRepo {

    public static function log(int $customerId, string $entityType, string $action, string $note = '', ?string $link = null): void {
        try {
            $uid = \App\Auth::user()['id'] ?? null;
            $pdo = DB::pdo();
            $stmt = $pdo->prepare("INSERT INTO customer_change_log (customer_id, user_id, entity_type, action_type, note, ref_link, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$customerId, $uid, $entityType, $action, $note, $link]);
        } catch (\Throwable $e) { error_log("Log Error: " . $e->getMessage()); }
    }

    public static function list(int $customerId): array {
        $st = DB::pdo()->prepare("SELECT l.*, u.email as user_email FROM customer_change_log l LEFT JOIN users u ON u.id = l.user_id WHERE l.customer_id = ? ORDER BY l.created_at DESC LIMIT 1000");
        $st->execute([$customerId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // NEU: Globale Liste für Audit Log
    public static function listGlobal(int $limit = 200, string $search = ''): array {
        $pdo = DB::pdo();
        $sql = "SELECT l.*, u.email as user_email, u.name as user_name, c.name as customer_name 
                FROM customer_change_log l
                LEFT JOIN users u ON u.id = l.user_id
                LEFT JOIN customers c ON c.id = l.customer_id
                WHERE 1=1";
        $params = [];
        if ($search) {
            $sql .= " AND (l.note LIKE ? OR c.name LIKE ? OR u.email LIKE ? OR l.action_type LIKE ?)";
            $t = "%$search%"; $params = [$t, $t, $t, $t];
        }
        $sql .= " ORDER BY l.created_at DESC LIMIT " . (int)$limit;
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function clear(int $customerId): bool {
        if (!\App\Policy::hasRole('Admin')) return false;
        return DB::pdo()->prepare("DELETE FROM customer_change_log WHERE customer_id = ?")->execute([$customerId]);
    }
}