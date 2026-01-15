<?php
namespace App;

class SystemRepo {

  public static function listByCustomer(int $customerId): array {
    $st = DB::pdo()->prepare("
      SELECT s.*, u.email AS technician_email
      FROM systems s
      LEFT JOIN users u ON u.id = s.responsible_technician_id
      WHERE s.customer_id = ?
      ORDER BY s.name ASC
    ");
    $st->execute([$customerId]);
    return $st->fetchAll() ?: [];
  }

  public static function find(int $id): ?array {
    $st = DB::pdo()->prepare("SELECT * FROM systems WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    return $st->fetch() ?: null;
  }

  public static function create(array $data): array {
    $errors = [];
    if (trim($data['name'] ?? '') === '') $errors[] = 'Systemname ist Pflicht.';
    if (($data['customer_id'] ?? 0) <= 0) $errors[] = 'Kunde fehlt.';
    if ($errors) return ['ok'=>false,'errors'=>$errors];

    $st = DB::pdo()->prepare("
      INSERT INTO systems (customer_id, name, type, role, version, install_date, responsible_technician_id, notes)
      VALUES (?,?,?,?,?,?,?,?)
    ");
    $st->execute([
      (int)$data['customer_id'],
      trim($data['name']),
      trim($data['type'] ?? '') ?: null,
      trim($data['role'] ?? '') ?: null,
      trim($data['version'] ?? '') ?: null,
      $data['install_date'] ?: null,
      (int)($data['responsible_technician_id'] ?? 0) ?: null,
      trim($data['notes'] ?? '') ?: null
    ]);
    return ['ok'=>true,'id'=>(int)DB::pdo()->lastInsertId()];
  }

  public static function update(int $id, array $data): array {
    $errors = [];
    if (trim($data['name'] ?? '') === '') $errors[] = 'Systemname ist Pflicht.';
    if ($errors) return ['ok'=>false,'errors'=>$errors];

    $st = DB::pdo()->prepare("
      UPDATE systems SET
        name=?, type=?, role=?, version=?, install_date=?, responsible_technician_id=?, notes=?
      WHERE id=? LIMIT 1
    ");
    $st->execute([
      trim($data['name']),
      trim($data['type'] ?? '') ?: null,
      trim($data['role'] ?? '') ?: null,
      trim($data['version'] ?? '') ?: null,
      $data['install_date'] ?: null,
      (int)($data['responsible_technician_id'] ?? 0) ?: null,
      trim($data['notes'] ?? '') ?: null,
      $id
    ]);
    return ['ok'=>true];
  }
  
	  public static function delete(int $id): bool {
	  $st = DB::pdo()->prepare("DELETE FROM systems WHERE id = ? LIMIT 1");
	  return $st->execute([$id]);
	}

}
