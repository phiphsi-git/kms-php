<?php
namespace App;

use PDO;

class FileRepo
{
    /**
     * Liste aller Dateien eines Kunden (eine Zeile pro Datei, keine Duplikate).
     * Aggregiert verknüpfte Systeme/Aufgaben als CSV.
     */
    public static function listByCustomer(int $customerId): array
    {
        $sql = "
            SELECT
              f.id,
              f.customer_id,
              f.stored_name,
              f.original_name,
              f.mime,
              f.size_bytes,
              f.description,
              f.uploaded_by,
              f.created_at,
              u.email AS uploader_email,
              GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ')   AS systems_csv,
              GROUP_CONCAT(DISTINCT t.title ORDER BY t.id DESC SEPARATOR ', ') AS tasks_csv
            FROM customer_files f
            LEFT JOIN users u ON u.id = f.uploaded_by
            LEFT JOIN customer_file_systems cfs ON cfs.file_id = f.id
            LEFT JOIN systems s ON s.id = cfs.system_id
            LEFT JOIN customer_file_tasks cft ON cft.file_id = f.id
            LEFT JOIN tasks t ON t.id = cft.task_id
            WHERE f.customer_id = ?
            GROUP BY
              f.id, f.customer_id, f.stored_name, f.original_name, f.mime, f.size_bytes,
              f.description, f.uploaded_by, f.created_at, u.email
            ORDER BY f.id DESC
        ";
        $st = DB::pdo()->prepare($sql);
        $st->execute([$customerId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Dateien zu einem System (nutzt Pivot-Tabelle).
     */
    public static function listBySystem(int $systemId): array
    {
        $sql = "
            SELECT f.*
            FROM customer_file_systems cfs
            JOIN customer_files f ON f.id = cfs.file_id
            WHERE cfs.system_id = ?
            ORDER BY f.id DESC
        ";
        $st = DB::pdo()->prepare($sql);
        $st->execute([$systemId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Dateien zu einer Aufgabe (nutzt Pivot-Tabelle).
     */
    public static function listByTask(int $taskId): array
    {
        $sql = "
            SELECT f.*
            FROM customer_file_tasks cft
            JOIN customer_files f ON f.id = cft.file_id
            WHERE cft.task_id = ?
            ORDER BY f.id DESC
        ";
        $st = DB::pdo()->prepare($sql);
        $st->execute([$taskId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Einzelne Datei finden.
     */
    public static function find(int $id): ?array
    {
        $st = DB::pdo()->prepare("SELECT * FROM customer_files WHERE id = ? LIMIT 1");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Upload + DB-Eintrag + Mehrfach-Zuordnung in Pivot-Tabellen.
     * Erwartet:
     *  - $data: customer_id, description, uploaded_by, system_ids[], task_ids[]
     *  - $file: $_FILES['file']-Array
     */
    public static function create(array $data, array $file): array
    {
        $errors = [];

        if (($data['customer_id'] ?? 0) <= 0) {
            $errors[] = 'Kunde fehlt.';
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'Datei-Upload fehlgeschlagen.';
        }
        if ($errors) return ['ok' => false, 'errors' => $errors];

        // Whitelist (anpassbar)
        $allowedExt = ['pdf','png','jpg','jpeg','gif','webp','txt','doc','docx','xls','xlsx','csv','ps1','sh','bat','cmd'];
        $name = $file['name'] ?? 'upload.bin';
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext && !in_array($ext, $allowedExt, true)) {
            return ['ok' => false, 'errors' => ['Dateityp nicht erlaubt.']];
        }

        $cid  = (int)$data['customer_id'];
        $base = rtrim(Config::STORAGE_DIR, '/') . "/customers/$cid/files";
        @mkdir($base, 0775, true);

        // eindeutiger Speichername
        $stored = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . ($ext ? '.' . $ext : '');
        $target = "$base/$stored";

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return ['ok' => false, 'errors' => ['Konnte Datei nicht speichern.']];
        }

        $mime = $file['type'] ?? null;
        $size = (int)($file['size'] ?? 0);

        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            // Basisdatensatz (alte system_id/task_id bleiben NULL)
            $st = $pdo->prepare("
                INSERT INTO customer_files
                  (customer_id, system_id, task_id, stored_name, original_name, mime, size_bytes, description, uploaded_by)
                VALUES (?,?,?,?,?,?,?,?,?)
            ");
            $st->execute([
                $cid,
                null,
                null,
                $stored,
                $name,
                $mime,
                $size,
                trim($data['description'] ?? '') ?: null,
                (int)($data['uploaded_by'] ?? 0) ?: null
            ]);
            $fileId = (int)$pdo->lastInsertId();

            // Mehrfach: Systeme
            $sysIds = array_filter(array_map('intval', $data['system_ids'] ?? []));
            if ($sysIds) {
                $ins = $pdo->prepare("INSERT IGNORE INTO customer_file_systems (file_id, system_id) VALUES (?, ?)");
                foreach ($sysIds as $sid) {
                    $ins->execute([$fileId, $sid]);
                }
            }

            // Mehrfach: Aufgaben
            $tskIds = array_filter(array_map('intval', $data['task_ids'] ?? []));
            if ($tskIds) {
                $ins = $pdo->prepare("INSERT IGNORE INTO customer_file_tasks (file_id, task_id) VALUES (?, ?)");
                foreach ($tskIds as $tid) {
                    $ins->execute([$fileId, $tid]);
                }
            }

            $pdo->commit();
            return ['ok' => true, 'id' => $fileId, 'stored' => $stored];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            if (is_file($target)) @unlink($target);
            return ['ok' => false, 'errors' => ['DB-Fehler: ' . $e->getMessage()]];
        }
    }

    /**
     * Datei + DB-Eintrag löschen (Pivots werden via FK-CASCADE entfernt).
     */
    public static function delete(int $id): bool
    {
        $f = self::find($id);
        if (!$f) return false;

        $cid  = (int)$f['customer_id'];
        $path = rtrim(Config::STORAGE_DIR, '/') . "/customers/$cid/files/" . $f['stored_name'];
        if (is_file($path)) {
            @unlink($path);
        }
        $d = DB::pdo()->prepare("DELETE FROM customer_files WHERE id = ? LIMIT 1");
        return $d->execute([$id]);
    }

    /**
     * Absoluten Pfad zu einer Datei liefern (Hilfsfunktion).
     */
    public static function absolutePath(array $f): string
    {
        return rtrim(Config::STORAGE_DIR, '/') . "/customers/{$f['customer_id']}/files/{$f['stored_name']}";
    }
}
