<?php
namespace App;

class Policy {
    
    // Prüft, ob der User eine bestimmte Berechtigung hat (z.B. 'tasks.create')
    public static function can(string $permission): bool {
        $user = Auth::user();
        if (!$user) return false;
        
        $role = $user['role'] ?? 'Mitarbeiter';

        // Admin darf alles
        if ($role === 'Admin') return true;

        // Definition der Rollen & Rechte
        $rules = [
            'Projektleiter' => [
                'customers.view', 'customers.create', 'customers.update',
                'systems.create', 'systems.update',
                'tasks.create', 'tasks.update',
                'files.view', 'files.upload', 'files.delete',
                'users.view'
            ],
            'LeitenderTechniker' => [
                'customers.view', 'customers.create', 'customers.update',
                'systems.create', 'systems.update',
                'tasks.create', 'tasks.update',
                'files.view', 'files.upload',
                'users.view'
            ],
            'Techniker' => [
                'customers.view', 'customers.update',
                'systems.create', 'systems.update',
                'tasks.create', 'tasks.update',
                'files.view', 'files.upload'
            ],
            'Mitarbeiter' => [
                'customers.view',
                'tasks.create', 'tasks.update', // Darf Status ändern
                'files.view', 'files.upload'
            ],
            'Lernender' => [
                'customers.view',
                'tasks.create', 'tasks.update',
                'files.view'
            ]
        ];

        $perms = $rules[$role] ?? [];
        return in_array($permission, $perms);
    }

    // Prüft, ob der User eine bestimmte Rolle (oder eine von mehreren) hat
    // NEU HINZUGEFÜGT
    public static function hasRole($roles): bool {
        $user = Auth::user();
        if (!$user) return false;
        
        $myRole = $user['role'] ?? '';
        
        if (is_array($roles)) {
            return in_array($myRole, $roles);
        }
        return $myRole === $roles;
    }

    // Helper für harte Checks (wirft Fehler statt bool)
    public static function enforce(string $permission): void {
        if (!self::can($permission)) {
            http_response_code(403);
            exit('Zugriff verweigert (Policy)');
        }
    }

    public static function canResetPasswordOf(array $actor, array $target): bool {
        if (($actor['role']??'') === 'Admin') return true;
        if (($actor['role']??'') === 'Projektleiter' && ($target['role']??'') !== 'Admin' && ($target['role']??'') !== 'Projektleiter') return true;
        return false;
    }
}