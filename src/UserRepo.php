<?php
namespace App;
use PDO;

class UserRepo {

  /** Mail → Benutzer laden (für Login) */
  public static function findByEmail(string $email): ?array {
    $st = DB::pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $u = $st->fetch();
    return $u ?: null;
  }

  /** Benutzer anlegen (mit Passwort-Policy) */
  public static function create(string $email, string $password, string $role): array {
    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ungültige E-Mail.';
    $pwErrors = PasswordPolicy::validate($password);
    $errors = array_merge($errors, $pwErrors);
    $allowed = ['Admin','Projektleiter','LeitenderTechniker','Techniker','Mitarbeiter','Lernender'];
    if (!in_array($role, $allowed, true)) $errors[] = 'Ungültige Rolle.';
    if ($errors) return ['ok'=>false,'errors'=>$errors];

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = DB::pdo()->prepare('INSERT INTO users (email, password_hash, role, is_active) VALUES (?, ?, ?, 1)');
    try {
      $st->execute([$email, $hash, $role]);
      return ['ok'=>true];
    } catch (\PDOException $e) {
      return ['ok'=>false,'errors'=>['E-Mail bereits vorhanden?']];
    }
  }

  /** Liste aller Benutzer (inkl. gesperrt), mit Suche & Sortierung */
  public static function listAll(string $q = '', string $sort = 'email_asc'): array {
    $pdo = DB::pdo();

    $where = '';
    $params = [];
    if ($q !== '') {
      // zwei Platzhalter benutzen → HY093 vermeiden
      $where = "WHERE (email LIKE :q1 OR role LIKE :q2)";
      $params[':q1'] = '%'.$q.'%';
      $params[':q2'] = '%'.$q.'%';
    }

    $order = match ($sort) {
      'email_desc' => 'email DESC',
      'role_asc'   => 'role ASC, email ASC',
      'role_desc'  => 'role DESC, email ASC',
      'status_desc'=> 'is_active DESC, email ASC',
      default      => 'email ASC',
    };

    $sql = "SELECT id, email, role, is_active FROM users $where ORDER BY $order LIMIT 1000";
    $st = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $st->bindValue($k, $v);
    $st->execute();
    return $st->fetchAll() ?: [];
  }

  /** Benutzer nach Rolle(n) (für Auswahlfelder etc.) */
  public static function listByRoles(array $roles): array {
    if (!$roles) return [];
    $in  = implode(',', array_fill(0, count($roles), '?'));
    $sql = "SELECT id, email, role, is_active FROM users WHERE role IN ($in) ORDER BY email";
    $st  = DB::pdo()->prepare($sql);
    $st->execute($roles);
    return $st->fetchAll() ?: [];
  }

  /** Benutzer laden (by id) */
  public static function findById(int $id): ?array {
    $st = DB::pdo()->prepare("SELECT id, email, role, is_active FROM users WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    return $st->fetch() ?: null;
  }

  /** Benutzer-Stammdaten ändern (E-Mail, Rolle, Sperre) */
  public static function updateUser(int $id, string $email, string $role, int $isActive): array {
    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ungültige E-Mail.';
    $allowed = ['Admin','Projektleiter','LeitenderTechniker','Techniker','Mitarbeiter','Lernender'];
    if (!in_array($role, $allowed, true)) $errors[] = 'Ungültige Rolle.';
    if ($errors) return ['ok'=>false,'errors'=>$errors];

    $st = DB::pdo()->prepare("UPDATE users SET email = ?, role = ?, is_active = ? WHERE id = ? LIMIT 1");
    try {
      $st->execute([$email, $role, $isActive ? 1 : 0, $id]);
      return ['ok'=>true];
    } catch (\PDOException $e) {
      return ['ok'=>false,'errors'=>['E-Mail bereits vergeben?']];
    }
  }

  /** Passwort neu setzen (Policy enforced) */
  public static function setPassword(int $id, string $newPassword): array {
    $errs = PasswordPolicy::validate($newPassword);
    if ($errs) return ['ok'=>false,'errors'=>$errs];
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $st = DB::pdo()->prepare("UPDATE users SET password_hash = ? WHERE id = ? LIMIT 1");
    $st->execute([$hash, $id]);
    return ['ok'=>true];
  }
}
