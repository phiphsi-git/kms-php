<?php
namespace App;

use PDO;

class SystemRepo {

    public static function listByCustomer(int $customerId): array {
        $st = DB::pdo()->prepare("SELECT * FROM systems WHERE customer_id = ? ORDER BY name ASC");
        $st->execute([$customerId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(int $id): ?array {
        $st = DB::pdo()->prepare("SELECT * FROM systems WHERE id = ? LIMIT 1");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(array $data): array {
        $errors = [];
        if (trim($data['name']??'') === '') $errors[] = 'Systemname fehlt.';
        if (($data['customer_id']??0) <= 0) $errors[] = 'Kunde fehlt.';
        if ($errors) return ['ok'=>false, 'errors'=>$errors];

        $sql = "INSERT INTO systems (customer_id, name, type, role, version, notes) VALUES (?,?,?,?,?,?)";
        $pdo = DB::pdo();
        try {
            $pdo->prepare($sql)->execute([
                $data['customer_id'],
                trim($data['name']),
                trim($data['type']??''),
                trim($data['role']??''),
                trim($data['version']??''),
                trim($data['notes']??'')
            ]);
            $id = (int)$pdo->lastInsertId();

            if (class_exists('\App\ChangeLogRepo')) {
                \App\ChangeLogRepo::log((int)$data['customer_id'], 'system', 'create', "System '{$data['name']}' erstellt.");
            }

            return ['ok'=>true, 'id'=>$id];
        } catch (\PDOException $e) {
            return ['ok'=>false, 'errors'=>[$e->getMessage()]];
        }
    }

    public static function update(int $id, array $data): array {
        if (trim($data['name']??'') === '') return ['ok'=>false, 'errors'=>['Name fehlt.']];
        $current = self::find($id);
        
        $sql = "UPDATE systems SET name=?, type=?, role=?, version=?, notes=? WHERE id=?";
        $pdo = DB::pdo();
        try {
            $pdo->prepare($sql)->execute([
                trim($data['name']),
                trim($data['type']??''),
                trim($data['role']??''),
                trim($data['version']??''),
                trim($data['notes']??''),
                $id
            ]);

            if ($current && class_exists('\App\ChangeLogRepo')) {
                $cid = (int)$current['customer_id'];
                \App\ChangeLogRepo::log($cid, 'system', 'update', "System '{$data['name']}' bearbeitet.");
            }

            return ['ok'=>true];
        } catch (\PDOException $e) {
            return ['ok'=>false, 'errors'=>[$e->getMessage()]];
        }
    }

    public static function delete(int $id): bool {
        $sys = self::find($id);
        if (!$sys) return false;

        $pdo = DB::pdo();
        $ok = $pdo->prepare("DELETE FROM systems WHERE id = ? LIMIT 1")->execute([$id]);

        if ($ok && class_exists('\App\ChangeLogRepo')) {
            $cid = (int)$sys['customer_id'];
            $name = $sys['name'];
            \App\ChangeLogRepo::log($cid, 'system', 'delete', "System '$name' gelöscht.");
        }

        return $ok;
    }
}