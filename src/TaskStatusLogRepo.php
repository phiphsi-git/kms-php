<?php
namespace App;

use PDO;

class TaskStatusLogRepo
{
    public static function log(int $taskId, string $status, ?string $comment, ?int $userId): void {
        $st = DB::pdo()->prepare("
            INSERT INTO task_status_log (task_id, status, comment, changed_by)
            VALUES (?,?,?,?)
        ");
        $st->execute([$taskId, $status, $comment ?: null, $userId ?: null]);
    }

    /** Letzter Status <= Stichtag (Report-Zeitpunkt) */
    public static function lastAsOf(int $taskId, \DateTimeInterface $asOf): ?array {
        $st = DB::pdo()->prepare("
            SELECT status, comment, changed_by, changed_at
            FROM task_status_log
            WHERE task_id = ? AND changed_at <= ?
            ORDER BY changed_at DESC, id DESC
            LIMIT 1
        ");
        $st->execute([$taskId, $asOf->format('Y-m-d H:i:s')]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
	
	public static function listRecent(int $taskId, int $limit = 10): array {
	  $st = DB::pdo()->prepare("
		SELECT tsl.*, u.email AS user_email
		FROM task_status_log tsl
		LEFT JOIN users u ON u.id = tsl.changed_by
		WHERE tsl.task_id = ?
		ORDER BY tsl.changed_at DESC, tsl.id DESC
		LIMIT ?
	  ");
	  $st->bindValue(1, $taskId, PDO::PARAM_INT);
	  $st->bindValue(2, $limit, PDO::PARAM_INT);
	  $st->execute();
	  return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}

}
