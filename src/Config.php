<?php
namespace App;

class Config {
    private static array $env = [];

    public static function load(): void {
        $path = __DIR__ . '/../.env';
        if (file_exists($path)) {
            static::$env = parse_ini_file($path);
        }
    }

    public static function get(string $key, $default = null) {
        return self::$env[$key] ?? $default;
    }

    // Grundlegende App-Einstellungen
    public const APP_NAME = 'KMS - Ph. Brandenberger';
    public const SESSION_NAME = 'kms_session';
    public const DB_CHARSET = 'utf8mb4';
    
    // Die vom Layout benötigte Konstante
    public const BASE_URL = '/'; 

    // Dynamischer Pfad für das QNAP Filesystem
    public static function storageDir(): string {
        return self::get('STORAGE_DIR', '/share/CACHEDEV1_DATA/Web/kms-php/storage/');
    }
}