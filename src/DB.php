<?php
namespace App;
use PDO;
use PDOException;

class DB {
  public static function pdo(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    // Abruf der Werte über Config::get() mit Fallbacks [cite: 270, 271, 273]
    $host = Config::get('DB_HOST', '127.0.0.1');
    $port = Config::get('DB_PORT', '3307');
    $name = Config::get('DB_NAME', 'kms');
    $user = Config::get('DB_USER', 'kms_app');
    $pass = Config::get('DB_PASS', 'Bernauer+8712');

    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=" . Config::DB_CHARSET;

    $opts = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
      $pdo = new PDO($dsn, $user, $pass, $opts);
    } catch (PDOException $e) {
      http_response_code(500);
      exit('DB-Verbindung fehlgeschlagen: '.$e->getMessage());
    }
    return $pdo;
  }
}