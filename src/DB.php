<?php
namespace App;
use PDO; use PDOException;

class DB {
  public static function pdo(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dsn = 'mysql:host='.Config::DB_HOST
         . ';port='.Config::DB_PORT
         . ';dbname='.Config::DB_NAME
         . ';charset='.Config::DB_CHARSET;

    $opts = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
      $pdo = new PDO($dsn, Config::DB_USER, Config::DB_PASS, $opts);
    } catch (PDOException $e) {
      http_response_code(500);
      exit('DB-Verbindung fehlgeschlagen: '.$e->getMessage());
    }
    return $pdo;
  }
}
