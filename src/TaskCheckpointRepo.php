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

    // NEU: Reset für wiederkehrende Aufgaben
    public static function resetByTask(int $taskId): void {
        $sql = "UPDATE task_checkpoints SET is_checked = 0, comment = NULL, updated_at = NOW() WHERE task_id = ?";
        DB::pdo()->prepare($sql)->execute([$taskId]);
    }

    public static function sync(int $taskId, array $payload): array {
        $ids = array_map('intval', $payload['ids']??[]); $labels = $payload['labels']??[]; $done = $payload['is_done']??[]; $reqCmt = $payload['require_comment_on_fail']??[]; $cmts = $payload['comments']??[]; $ords = $payload['orders']??[];
        $rows = []; $n = max(count($labels), count($ids));
        for ($i=0; $i<$n; $i++) {
            $label = trim($labels[$i]??''); if($label==='') continue;
            $rows[] = ['id'=>(int)($ids[$i]??0), 'label'=>$label, 'is_done'=>!empty($done[$i])?1:0, 'req'=>!empty($reqCmt[$i])?1:0, 'comment'=>trim($cmts[$i]??'')?:null, 'order'=>(int)($ords[$i]??$i)];
        }
        
        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("SELECT id FROM task_checkpoints WHERE task_id=?"); $st->execute([$taskId]);
            $existing = array_map('intval', array_column($st->fetchAll(PDO::FETCH_ASSOC), 'id'));
            $keep = [];
            foreach ($rows as $r) {
                if ($r['id'] && in_array($r['id'], $existing, true)) {
                    $pdo->prepare("UPDATE task_checkpoints SET label=?, is_done=?, require_comment_on_fail=?, comment=?, sort_order=?, updated_at=NOW() WHERE id=? AND task_id=?")->execute([$r['label'],$r['is_done'],$r['req'],$r['comment'],$r['order'],$r['id'],$taskId]);
                    $keep[] = $r['id'];
                } else {
                    $pdo->prepare("INSERT INTO task_checkpoints (task_id, label, is_done, require_comment_on_fail, comment, sort_order) VALUES (?,?,?,?,?,?)")->execute([$taskId,$r['label'],$r['is_done'],$r['req'],$r['comment'],$r['order']]);
                    $keep[] = (int)$pdo->lastInsertId();
                }
            }
            if ($existing) {
                $toDelete = array_diff($existing, $keep);
                if ($toDelete) $pdo->prepare("DELETE FROM task_checkpoints WHERE task_id=? AND id IN (".implode(',',array_fill(0,count($toDelete),'?')).")")->execute(array_merge([$taskId], array_values($toDelete)));
            }
            $pdo->commit();
            return ['ok'=>true];
        } catch (\Throwable $e) {
            $pdo->rollBack(); return ['ok'=>false,'errors'=>[$e->getMessage()]];
        }
    }
}