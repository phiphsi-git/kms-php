<?php
namespace App;

use PDO;

class NetworkRepo {

    public static function listByCustomer(int $cid): array {
        // Sortiert nach Standort, dann VLAN ID
        $st = DB::pdo()->prepare("SELECT * FROM customer_networks WHERE customer_id = ? ORDER BY site_name ASC, vlan_id ASC");
        $st->execute([$cid]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(array $data): array {
        if (empty($data['name']) || empty($data['subnet'])) return ['ok'=>false, 'errors'=>['Name und Subnetz sind Pflicht.']];

        $sql = "INSERT INTO customer_networks (customer_id, site_name, name, vlan_id, subnet, gateway, dhcp_range, dns_servers, notes) VALUES (?,?,?,?,?,?,?,?,?)";
        
        DB::pdo()->prepare($sql)->execute([
            (int)$data['customer_id'],
            trim($data['site_name'] ?? 'Hauptstandort'),
            trim($data['name']),
            !empty($data['vlan_id']) ? (int)$data['vlan_id'] : null,
            trim($data['subnet']),
            trim($data['gateway'] ?? ''),
            trim($data['dhcp_range'] ?? ''),
            trim($data['dns_servers'] ?? ''),
            trim($data['notes'] ?? '')
        ]);

        if (class_exists('\App\ChangeLogRepo')) \App\ChangeLogRepo::log((int)$data['customer_id'], 'network', 'create', "Netzwerk '{$data['name']}' angelegt.");
        return ['ok'=>true];
    }

    public static function delete(int $id): bool {
        $st = DB::pdo()->prepare("SELECT customer_id, name FROM customer_networks WHERE id=?");
        $st->execute([$id]);
        $net = $st->fetch();
        if (!$net) return false;

        DB::pdo()->prepare("DELETE FROM customer_networks WHERE id=?")->execute([$id]);
        if (class_exists('\App\ChangeLogRepo')) \App\ChangeLogRepo::log((int)$net['customer_id'], 'network', 'delete', "Netzwerk '{$net['name']}' gelöscht.");
        return true;
    }
}