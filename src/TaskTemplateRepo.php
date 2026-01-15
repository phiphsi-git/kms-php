<?php
namespace App;
use PDO;

class TaskTemplateRepo {
  public static function listAll(): array {
    return DB::pdo()->query("SELECT * FROM task_templates ORDER BY title")->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
  public static function checkpoints(int $templateId): array {
    $st = DB::pdo()->prepare("SELECT * FROM task_template_checkpoints WHERE template_id=? ORDER BY sort_order,id");
    $st->execute([$templateId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
}
