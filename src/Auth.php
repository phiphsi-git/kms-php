<?php
namespace App;

class Auth {
    
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Session-Parameter für mehr Sicherheit
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_strict_mode', 1);
            session_start();
        }
    }

    public static function check(): bool {
        return isset($_SESSION['user']['id']);
    }

    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    // Diese Methode hat gefehlt!
    public static function login(array $user): void {
        session_regenerate_id(true); // Session-Fixation verhindern
        $_SESSION['user'] = [
            'id'    => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role'] ?? 'Mitarbeiter',
            'name'  => $user['name'] ?? '',
            'totp_secret' => $user['totp_secret'] ?? null
        ];
    }

    // Deine alte Methode (bleibt erhalten für normalen Login ohne 2FA Check im index)
    public static function attempt(string $email, string $password): bool {
        $user = UserRepo::findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            if (($user['is_active'] ?? 1) == 0) return false;
            
            // Hier rufen wir jetzt intern die neue login-Methode auf
            self::login($user);
            return true;
        }
        return false;
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}