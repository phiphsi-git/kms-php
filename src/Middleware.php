<?php
namespace App;

class Middleware {
  public static function requireAuth(): void {
    if (!Auth::check()) { header('Location: '.Config::BASE_URL.'?route=login'); exit; }
  }
  public static function requireRole(array $roles): void {
    self::requireAuth();
    $u = Auth::user();
    if (!$u || !in_array($u['role'], $roles, true)) {
      http_response_code(403);
      exit('403 Forbidden – unzureichende Berechtigung.');
    }
  }
}
