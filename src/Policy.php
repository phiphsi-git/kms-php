<?php
namespace App;

class Policy {
  // Rangfolge für "downward"-Resets
  public static array $roleRank = [
    'Admin' => 5,
    'Projektleiter' => 4,
    'LeitenderTechniker' => 3,
    'Techniker' => 2,
    'Mitarbeiter' => 1,
    'Lernender' => 0,
  ];

  // Capability-Mapping (Beispiele; passe an deine bestehende Datei an)
  public static function capsFor(string $role): array {
    switch ($role) {
// Beispiele:
case 'Admin': return ['*'];
case 'Projektleiter':
  return [
    'customers.view','customers.create','customers.update','customers.delete',
    'systems.view','systems.create','systems.update',
    'tasks.view','tasks.create','tasks.update','tasks.pause',
	'files.upload','files.delete','files.view'
  ];
case 'LeitenderTechniker':
  return [
    'customers.view','customers.update',
    'systems.view','systems.create','systems.update',
    'tasks.view','tasks.create','tasks.update','tasks.pause',
	'files.upload','files.delete','files.view'
  ];
case 'Techniker':
  return [
    'customers.view',
    'systems.view','systems.create',
    'tasks.view','tasks.create','tasks.update',
	'files.view'
  ];
case 'Mitarbeiter':
  return [
    'customers.view','systems.view','tasks.view','tasks.create',
	'files.view'
  ];
case 'Lernender':
  return [
    'customers.view','systems.view','tasks.view','files.view'
  ];

        return ['users.view']; // sehen ihre eigenen Daten / Liste
      default: return [];
    }
  }

  public static function can(string $cap): bool {
    $u = Auth::user();
    if (!$u) return false;
    $caps = self::capsFor($u['role'] ?? '');
    if (in_array('*', $caps, true)) return true;
    return in_array($cap, $caps, true);
  }

  // Darf $actor das PW von $target setzen (downward)?
  public static function canResetPasswordOf(array $actor, array $target): bool {
    // Admin immer ja
    if (self::can('users.manage')) return true;
    // Nur "downward"
    if (!self::can('users.reset_password_downward')) return false;
    $ar = self::$roleRank[$actor['role']] ?? -1;
    $tr = self::$roleRank[$target['role']] ?? 99;
    return $ar > $tr; // streng "downward": höherer Rang als Ziel
  }
  
  public static function enforce(string $perm): void {
  if (!self::can($perm)) {
    http_response_code(403);
    exit('Forbidden');
  }
}

}
