<?php
namespace App;

use PDO;

class LicenseRepo {

    public static function listByCustomer(int $cid): array {
        $pdo = DB::pdo();
        // Lizenzen laden
        $st = $pdo->prepare("SELECT * FROM customer_licenses WHERE customer_id = ? ORDER BY valid_until ASC");
        $st->execute([$cid]);
        $licenses = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Verknüpfte Systeme dazu laden
        foreach ($licenses as &$lic) {
            $sSt = $pdo->prepare("
                SELECT s.name 
                FROM systems s 
                JOIN license_systems ls ON ls.system_id = s.id 
                WHERE ls.license_id = ?
            ");
            $sSt->execute([$lic['id']]);
            $lic['system_names'] = $sSt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }
        return $licenses;
    }

    // Für Dashboard: Ablaufende Lizenzen (Global)
    public static function getExpiring(int $days = 90): array {
        $sql = "
            SELECT l.*, c.name as customer_name 
            FROM customer_licenses l
            JOIN customers c ON c.id = l.customer_id
            WHERE l.valid_until IS NOT NULL 
            AND l.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY l.valid_until ASC
        ";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(array $data, array $file): array {
        if (empty($data['software_name'])) return ['ok'=>false, 'errors'=>['Name fehlt']];

        $pdo = DB::pdo();
        $pdo->beginTransaction();

        try {
            // 1. Datei Upload (falls vorhanden)
            $filePath = null;
            if (($file['error'] ?? 4) === 0) {
                $dir = rtrim(Config::storageDir(), '/') . "/customers/{$data['customer_id']}/files";
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $cleanName = preg_replace('/[^a-z0-9]/i', '_', $data['software_name']) . '_lic.' . $ext;
                $target = $dir . '/' . uniqid() . '_' . $cleanName;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $filePath = $target;
                }
            }

            // 2. Lizenz speichern
            $stmt = $pdo->prepare("
                INSERT INTO customer_licenses 
                (customer_id, type, software_name, vendor, license_key, valid_from, valid_until, seats, url, notes, file_path) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                (int)$data['customer_id'],
                $data['type'] ?? 'Software',
                trim($data['software_name']),
                trim($data['vendor'] ?? ''),
                trim($data['license_key'] ?? ''),
                !empty($data['valid_from']) ? $data['valid_from'] : null,
                !empty($data['valid_until']) ? $data['valid_until'] : null,
                (int)($data['seats'] ?? 1),
                trim($data['url'] ?? ''),
                trim($data['notes'] ?? ''),
                $filePath
            ]);
            $licId = $pdo->lastInsertId();

            // 3. Systeme verknüpfen
            if (!empty($data['system_ids']) && is_array($data['system_ids'])) {
                $insSys = $pdo->prepare("INSERT INTO license_systems (license_id, system_id) VALUES (?, ?)");
                foreach ($data['system_ids'] as $sid) {
                    $insSys->execute([$licId, (int)$sid]);
                }
            }

            if (class_exists('\App\ChangeLogRepo')) \App\ChangeLogRepo::log((int)$data['customer_id'], 'license', 'create', "Eintrag erstellt: {$data['software_name']}");

            $pdo->commit();
            return ['ok'=>true];

        } catch (\Throwable $e) {
            $pdo->rollBack();
            return ['ok'=>false, 'errors'=>[$e->getMessage()]];
        }
    }

    public static function delete(int $id): bool {
        $st = DB::pdo()->prepare("SELECT customer_id, software_name, file_path FROM customer_licenses WHERE id=?");
        $st->execute([$id]);
        $l = $st->fetch();
        if (!$l) return false;

        DB::pdo()->prepare("DELETE FROM customer_licenses WHERE id=?")->execute([$id]);
        
        // Datei löschen
        if (!empty($l['file_path']) && file_exists($l['file_path'])) {
            @unlink($l['file_path']);
        }

        if (class_exists('\App\ChangeLogRepo')) \App\ChangeLogRepo::log((int)$l['customer_id'], 'license', 'delete', "Gelöscht: {$l['software_name']}");
        return true;
    }

    // Export CSV (aktualisiert auf neue Felder)
    public static function exportCsv(int $customerId): void {
        $list = self::listByCustomer($customerId);
        $cust = CustomerRepo::findWithDetails($customerId);
        $filename = "Inventar_" . preg_replace('/[^a-z0-9]/i', '_', $cust['name']) . "_" . date('Ymd') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Typ', 'Software', 'Hersteller', 'Key', 'Gültig Bis', 'Anzahl', 'Systeme', 'Notiz']);

        foreach ($list as $row) {
            fputcsv($out, [
                $row['type'],
                $row['software_name'],
                $row['vendor'],
                $row['license_key'],
                $row['valid_until'],
                $row['seats'],
                implode(', ', $row['system_names']),
                $row['notes']
            ]);
        }
        fclose($out);
        exit;
    }
    
    // Import Dummy (Falls benötigt, muss an neue Struktur angepasst werden - hier gekürzt)
    public static function importCsv(int $cid, array $file): array { return ['ok'=>true]; }
}