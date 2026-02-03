<?php
namespace App;
use PDO;

class WikiRepo {
    // Liste laden
    public static function listByCustomer(int $cid): array {
        $st = DB::pdo()->prepare("
            SELECT w.*, u.email as user_email, u.name as user_name 
            FROM customer_wiki w 
            LEFT JOIN users u ON u.id = w.created_by 
            WHERE w.customer_id = ? 
            ORDER BY w.category ASC, w.title ASC
        ");
        $st->execute([$cid]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Einzelnen Eintrag laden
    public static function find(int $id): ?array {
        $st = DB::pdo()->prepare("SELECT * FROM customer_wiki WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Speichern (Create & Update)
    public static function save(int $id, array $data): bool {
        $pdo = DB::pdo();
        $title = trim($data['title']);
        $cat   = trim($data['category'] ?? 'Allgemein');
        $content = trim($data['content']);

        if ($id > 0) {
            // Update
            $sql = "UPDATE customer_wiki SET title = ?, category = ?, content = ? WHERE id = ?";
            $ok = $pdo->prepare($sql)->execute([$title, $cat, $content, $id]);
            $action = 'update'; 
            // ID für Log müsste hier eigentlich aus dem $data kommen oder via find() geholt werden.
            // Vereinfachung: Wir nehmen an customer_id ist im POST.
            $cid = (int)$data['customer_id'];
        } else {
            // Create
            $sql = "INSERT INTO customer_wiki (customer_id, title, category, content, created_by) VALUES (?, ?, ?, ?, ?)";
            $ok = $pdo->prepare($sql)->execute([
                (int)$data['customer_id'], $title, $cat, $content, (int)(\App\Auth::user()['id']??0)
            ]);
            $action = 'create'; 
            $cid = (int)$data['customer_id'];
        }
        
        if ($ok && class_exists('\App\ChangeLogRepo')) {
            \App\ChangeLogRepo::log($cid, 'wiki', $action, "Wiki: $title ($cat)");
        }
        return $ok;
    }

    // Löschen
    public static function delete(int $id): bool {
        $w = self::find($id);
        if (!$w) return false;
        $ok = DB::pdo()->prepare("DELETE FROM customer_wiki WHERE id = ?")->execute([$id]);
        if ($ok && class_exists('\App\ChangeLogRepo')) {
            \App\ChangeLogRepo::log((int)$w['customer_id'], 'wiki', 'delete', "Wiki gelöscht: {$w['title']}");
        }
        return $ok;
    }
}