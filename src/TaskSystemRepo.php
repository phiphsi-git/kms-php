<?php
namespace App;

final class TaskSystemRepo
{
  /** @return int[] */
  public static function listIds(int $taskId): array
  {
    $st = DB::pdo()->prepare("SELECT system_id FROM task_system WHERE task_id=? ORDER BY system_id");
    $st->execute([$taskId]);
    return array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN) ?: []);
  }

  /** @param array<int,mixed> $systemIds */
  public static function sync(int $taskId, array $systemIds): array
  {
    $systemIds = array_values(array_unique(array_filter(array_map('intval', $systemIds), fn($x)=>$x>0)));

    $pdo = DB::pdo();
    $pdo->beginTransaction();
    try {
      $pdo->prepare("DELETE FROM task_system WHERE task_id=?")->execute([$taskId]);

      if ($systemIds) {
        $ins = $pdo->prepare("INSERT INTO task_system (task_id, system_id) VALUES (?, ?)");
        foreach ($systemIds as $sid) {
          $ins->execute([$taskId, $sid]);
        }
      }

      $pdo->commit();
      return ['ok'=>true];
    } catch (\Throwable $e) {
      $pdo->rollBack();
      error_log('TaskSystemRepo::sync error: '.$e->getMessage());
      return ['ok'=>false, 'errors'=>['Fehler beim Speichern der Systeme.']];
    }
  }
}
