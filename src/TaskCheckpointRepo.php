<?php
namespace App;

use PDO;

class TaskCheckpointRepo
{
    public static function listByTask(int $taskId): array {
        $st = DB::pdo()->prepare("SELECT * FROM task_checkpoints WHERE task_id=? ORDER BY sort_order, id");
        $st->execute([$taskId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Sync: nimmt Arrays aus dem Formular entgegen und synchronisiert alle Punkte.
     * Erwartete Felder (parallel indiziert):
     * ids[], labels[], is_done[], require_comment_on_fail[], comments[], orders[]
     */
    public static function sync(int $taskId, array $payload): array {
        $ids    = array_map('intval', $payload['ids']    ?? []);
        $labels = $payload['labels'] ?? [];
        $done   = $payload['is_done'] ?? [];
        $reqCmt = $payload['require_comment_on_fail'] ?? [];
        $cmts   = $payload['comments'] ?? [];
        $ords   = $payload['orders'] ?? [];

        $rows = [];
        $n = max(count($labels), count($ids), count($ords));
        for ($i=0; $i<$n; $i++) {
            $label = trim($labels[$i] ?? '');
            if ($label === '') continue; // leere Zeilen ignorieren
            $rows[] = [
                'id'     => (int)($ids[$i] ?? 0),
                'label'  => $label,
                'is_done'=> !empty($done[$i]) ? 1 : 0,
                'req'    => !empty($reqCmt[$i]) ? 1 : 0,
                'comment'=> trim($cmts[$i] ?? '') ?: null,
                'order'  => (int)($ords[$i] ?? $i),
            ];
        }

        // Validierung: Kommentar nötig wenn is_done=0 und req=1
        $errors = [];
        foreach ($rows as $r) {
            if ($r['is_done'] === 0 && $r['req'] === 1 && ($r['comment'] === null || $r['comment'] === '')) {
                $errors[] = 'Kommentar erforderlich für nicht erfolgreichen Kontrollpunkt: "'.$r['label'].'"';
            }
        }
        if ($errors) return ['ok'=>false,'errors'=>$errors];

        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            // bestehende IDs für Vergleich
            $st = $pdo->prepare("SELECT id FROM task_checkpoints WHERE task_id=?");
            $st->execute([$taskId]);
            $existing = array_map('intval', array_column($st->fetchAll(PDO::FETCH_ASSOC), 'id'));

            $keep = [];
            // upsert/insert
            foreach ($rows as $r) {
                if ($r['id'] && in_array($r['id'], $existing, true)) {
                    $up = $pdo->prepare("UPDATE task_checkpoints
                        SET label=?, is_done=?, require_comment_on_fail=?, comment=?, sort_order=?, updated_at=NOW()
                        WHERE id=? AND task_id=?");
                    $up->execute([$r['label'],$r['is_done'],$r['req'],$r['comment'],$r['order'],$r['id'],$taskId]);
                    $keep[] = $r['id'];
                } else {
                    $ins = $pdo->prepare("INSERT INTO task_checkpoints
                        (task_id, label, is_done, require_comment_on_fail, comment, sort_order)
                        VALUES (?,?,?,?,?,?)");
                    $ins->execute([$taskId,$r['label'],$r['is_done'],$r['req'],$r['comment'],$r['order']]);
                    $keep[] = (int)$pdo->lastInsertId();
                }
            }

            // löschen, was nicht mehr gesendet wurde
            if ($existing) {
                $toDelete = array_diff($existing, $keep);
                if ($toDelete) {
                    $in = implode(',', array_fill(0, count($toDelete), '?'));
                    $del = $pdo->prepare("DELETE FROM task_checkpoints WHERE task_id=? AND id IN ($in)");
                    $del->execute(array_merge([$taskId], array_values($toDelete)));
                }
            }

            $pdo->commit();
            return ['ok'=>true];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return ['ok'=>false,'errors'=>['DB-Fehler (Checkpoints): '.$e->getMessage()]];
        }
    }
}
