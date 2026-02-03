<?php
namespace App;

final class SystemCheckpointTemplateRepo
{
  public static function listBySystem(int $systemId): array
  {
    $st = DB::pdo()->prepare("
      SELECT id, system_id, label, require_comment_on_fail, sort_order, is_active
      FROM system_checkpoint_templates
      WHERE system_id = ? AND is_active = 1
      ORDER BY sort_order ASC, id ASC
    ");
    $st->execute([$systemId]);
    return $st->fetchAll() ?: [];
  }

  /** @param int[] $systemIds */
  public static function listBySystems(array $systemIds): array
  {
    $systemIds = array_values(array_unique(array_filter(array_map('intval', $systemIds), fn($x)=>$x>0)));
    if (!$systemIds) return [];

    $in = implode(',', array_fill(0, count($systemIds), '?'));
    $st = DB::pdo()->prepare("
      SELECT system_id, label, require_comment_on_fail, sort_order
      FROM system_checkpoint_templates
      WHERE system_id IN ($in) AND is_active = 1
      ORDER BY system_id ASC, sort_order ASC, id ASC
    ");
    $st->execute($systemIds);
    return $st->fetchAll() ?: [];
  }
}
