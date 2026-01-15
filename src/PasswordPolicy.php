<?php
namespace App;

class PasswordPolicy {
  // Mind. 12 Zeichen, Groß-/Kleinbuchstaben, Zahl, Sonderzeichen
  public static function validate(string $pw): array {
    $errors = [];
    if (strlen($pw) < 12) $errors[] = 'Mindestens 12 Zeichen.';
    if (!preg_match('/[A-Z]/', $pw)) $errors[] = 'Mindestens ein Großbuchstabe.';
    if (!preg_match('/[a-z]/', $pw)) $errors[] = 'Mindestens ein Kleinbuchstabe.';
    if (!preg_match('/\\d/', $pw))   $errors[] = 'Mindestens eine Ziffer.';
    if (!preg_match('/[^A-Za-z0-9]/', $pw)) $errors[] = 'Mindestens ein Sonderzeichen.';
    return $errors;
  }
}
