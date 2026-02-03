<?php
namespace App;

class Config {
    private static array $env = [];

    public static function load(): void {
        // Pfad zur .env Datei (eine Ebene über src)
        $path = dirname(__DIR__) . '/.env';
        if (file_exists($path)) {
            $data = @parse_ini_file($path);
            if ($data !== false) {
                self::$env = $data;
            }
        }
    }

    public static function get(string $key, $default = null) {
        return self::$env[$key] ?? $default;
    }

    // --- Zentrale Methode für den Speicherpfad ---
    // Holt den Pfad aus der .env oder nutzt den QNAP-Standard
    public static function storageDir(): string {
        return self::get('STORAGE_DIR', '/share/CACHEDEV1_DATA/Web/kms-php/storage/');
    }

    // --- Konstanten ---
    public const APP_NAME     = 'KMS - Ph. Brandenberger';
    public const DB_CHARSET   = 'utf8mb4';
    public const SESSION_NAME = 'kms_session';
    public const BASE_URL     = '/'; 
}