<?php
namespace App;

use PDO;

class FileRepo
{
    /**
     * Erstellt eine Datei und verknüpft sie optional mit Systemen und Tasks.
     */
    public static function create(array $data, array $file): array {
        if (($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            return ['ok'=>false, 'errors'=>['Upload Fehler (Code '.($file['error']??'unknown').')']];
        }
        
        $customerId = (int)($data['customer_id'] ?? 0);
        if ($customerId <= 0) return ['ok'=>false, 'errors'=>['Kunde fehlt.']];

        $baseDir = rtrim(Config::storageDir(), '/')."/customers/$customerId/files";
        if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // Generiere sicheren Dateinamen
        $hashName = uniqid('f_').'.'.$ext;
        $target = $baseDir . '/' . $hashName;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $pdo = DB::pdo();
            $pdo->beginTransaction();
            try {
                // 1. Datei in DB speichern (Angepasst an deine Tabelle!)
                $stmt = $pdo->prepare("
                    INSERT INTO customer_files 
                    (customer_id, original_name, stored_name, filename, file_path, mime, size_bytes, description, uploaded_by) 
                    VALUES (?,?,?,?,?,?,?,?,?)
                ");
                
                $stmt->execute([
                    $customerId,
                    $file['name'],                      // original_name
                    $hashName,                          // stored_name (Pflichtfeld!)
                    $hashName,                          // filename (redundant, aber sicherheitshalber)
                    $target,                            // file_path
                    $file['type'] ?? 'application/octet-stream', // mime
                    (int)($file['size'] ?? 0),          // size_bytes
                    trim($data['description'] ?? ''),   // description
                    (int)($data['uploaded_by'] ?? 0) ?: null // uploaded_by
                ]);
                
                $fileId = (int)$pdo->lastInsertId();

                // 2. Verknüpfung mit Systemen (Mehrfachauswahl via Junction-Table)
                if (!empty($data['system_ids']) && is_array($data['system_ids'])) {
                    // Prüfen, ob wir die Junction-Table nutzen können.
                    // Falls nicht, versuchen wir das alte Feld 'system_id' mit dem ersten Wert zu füllen als Fallback
                    try {
                        $insSys = $pdo->prepare("INSERT INTO file_system (file_id, system_id) VALUES (?, ?)");
                        foreach ($data['system_ids'] as $sid) {
                            $insSys->execute([$fileId, (int)$sid]);
                        }
                    } catch (\Throwable $e) {
                        // Fallback: Wenn file_system Tabelle fehlt, update das legacy Feld in customer_files
                        if(count($data['system_ids']) > 0) {
                           $pdo->prepare("UPDATE customer_files SET system_id = ? WHERE id = ?")->execute([(int)$data['system_ids'][0], $fileId]);
                        }
                    }
                }

                // 3. Verknüpfung mit Tasks
                if (!empty($data['task_ids']) && is_array($data['task_ids'])) {
                    try {
                        $insTask = $pdo->prepare("INSERT INTO file_task (file_id, task_id) VALUES (?, ?)");
                        foreach ($data['task_ids'] as $tid) {
                            $insTask->execute([$fileId, (int)$tid]);
                        }
                    } catch (\Throwable $e) {
                        // Fallback Legacy
                         if(count($data['task_ids']) > 0) {
                           $pdo->prepare("UPDATE customer_files SET task_id = ? WHERE id = ?")->execute([(int)$data['task_ids'][0], $fileId]);
                        }
                    }
                }

                // 4. Logging
                if (class_exists('\App\ChangeLogRepo')) {
                    \App\ChangeLogRepo::log($customerId, 'file', 'upload', "Datei hochgeladen: {$file['name']}");
                }

                $pdo->commit();
                return ['ok'=>true, 'id'=>$fileId];

            } catch (\Throwable $e) {
                $pdo->rollBack();
                // Datei wieder löschen bei DB-Fehler
                @unlink($target);
                return ['ok'=>false, 'errors'=>['DB Fehler: '.$e->getMessage()]];
            }
        }
        
        return ['ok'=>false, 'errors'=>['Datei konnte nicht verschoben werden.']];
    }

    // UPDATE: Angepasst an die Spaltennamen beim Auslesen
    public static function listByCustomer(int $cid): array {
        // Wir mappen size_bytes auf size zurück, damit der View funktioniert
        $st = DB::pdo()->prepare("
            SELECT id, customer_id, original_name, stored_name, filename, mime, 
                   size_bytes AS size, description, uploaded_by AS created_by, created_at, file_path 
            FROM customer_files 
            WHERE customer_id=? 
            ORDER BY created_at DESC
        ");
        $st->execute([$cid]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(int $id): ?array {
        $st = DB::pdo()->prepare("SELECT *, size_bytes AS size FROM customer_files WHERE id=? LIMIT 1");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    // UPDATE: Falls alte Dateien keinen 'file_path' haben, bauen wir ihn aus 'stored_name'
    public static function absolutePath(array $file): string {
        if (!empty($file['file_path']) && file_exists($file['file_path'])) {
            return $file['file_path'];
        }
        // Fallback für alte Logik
        $base = rtrim(Config::storageDir(), '/')."/customers/{$file['customer_id']}/files";
        return $base . '/' . ($file['stored_name'] ?? $file['filename']);
    }

    public static function delete(int $id): bool {
        $file = self::find($id);
        if (!$file) return false;

        $pdo = DB::pdo();
        $ok = $pdo->prepare("DELETE FROM customer_files WHERE id=? LIMIT 1")->execute([$id]);
        
        if ($ok) {
            $path = self::absolutePath($file);
            if (file_exists($path)) @unlink($path);
            
            if (class_exists('\App\ChangeLogRepo')) {
                \App\ChangeLogRepo::log((int)$file['customer_id'], 'file', 'delete', "Datei gelöscht: {$file['original_name']}");
            }
        }
        return $ok;
    }
    
    // Legacy-Alias, falls irgendwo save() aufgerufen wird
    public static function save(int $cid, array $file, string $desc = ''): array {
        return self::create([
            'customer_id' => $cid,
            'description' => $desc,
            'uploaded_by' => (int)(\App\Auth::user()['id'] ?? 0)
        ], $file);
    }
}