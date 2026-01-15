<?php
namespace App;

class Config {
  public const DB_HOST    = '127.0.0.1';   // WICHTIG: NICHT 'localhost'
  public const DB_PORT    = 3307;          // MariaDB 10 Standard auf QNAP
  public const DB_NAME    = 'kms';
  public const DB_USER    = 'kms_app';
  public const DB_PASS    = 'Bernauer+8712';
  public const DB_CHARSET = 'utf8mb4';

  public const APP_NAME     = 'KMS - Ph. Brandenberger';
  public const BASE_URL     = '/';
  public const SESSION_NAME = 'kms_session';
  
  public const STORAGE_DIR = '/share/CACHEDEV1_DATA/Web/kms-php/storage/'; // Kundendaten Ablage, anpassen falls nötig
}