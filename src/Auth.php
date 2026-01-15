<?php
namespace App;

class Auth {
  public static function start(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_name(Config::SESSION_NAME);
      session_start();
    }
  }

  public static function attempt(string $email, string $password): bool {
    $user = UserRepo::findByEmail($email);
    if (!$user || !$user['is_active']) return false;
    if (!password_verify($password, $user['password_hash'])) return false;

    $_SESSION['user'] = [
      'id'    => (int)$user['id'],
      'email' => $user['email'],
      'role'  => $user['role'],
    ];
    return true;
  }

  public static function user(): ?array { return $_SESSION['user'] ?? null; }
  public static function check(): bool { return isset($_SESSION['user']); }
  public static function logout(): void { $_SESSION = []; session_destroy(); }
}
