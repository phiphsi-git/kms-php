<?php
// Daten laden
$users = \App\UserRepo::listAll();

// Helper für Initials-Avatar
function getInitials($name, $email) {
    $val = trim($name ? $name : $email);
    return strtoupper(substr($val, 0, 2));
}

// Helper für Rollen-Farben
function getRoleColor($role) {
    return match($role) {
        'Admin' => '#6f42c1', // Lila
        'Projektleiter' => '#007bff', // Blau
        'LeitenderTechniker' => '#17a2b8', // Cyan
        'Techniker' => '#28a745', // Grün
        'Lernender' => '#fd7e14', // Orange
        default => '#6c757d' // Grau
    };
}
?>
<style>
.user-row { display: flex; align-items: center; padding: 15px 20px; border-bottom: 1px solid #eee; transition: background 0.1s; }
.user-row:hover { background: #f9f9f9; }
.user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #eee; color: #555; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; font-size: 0.9em; }
.role-badge { padding: 3px 8px; border-radius: 12px; font-size: 0.75em; color: #fff; font-weight: 600; text-transform: uppercase; }
</style>

<section class="dash" style="max-width:1000px; margin:0 auto; padding:20px;">

  <header class="dash-header">
    <div class="dash-title">
        <h2>Benutzerverwaltung</h2>
        <div class="muted"><?= count($users) ?> aktive Zugänge</div>
    </div>
    <div class="actions">
        <a href="?route=dashboard" class="btn-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Dashboard
        </a>
        <a href="?route=user_new" class="btn-icon btn-primary" style="background:#0056b3; color:#fff; border:none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Benutzer anlegen
        </a>
    </div>
  </header>

  <div class="content-card">
    <div class="card-head" style="background:#f8f9fa; border-bottom:1px solid #eee; padding:10px 20px; font-weight:bold; color:#666; font-size:0.9em; display:flex;">
        <div style="flex:1;">Name / E-Mail</div>
        <div style="width:150px;">Rolle</div>
        <div style="width:100px;">Status</div>
        <div style="width:50px; text-align:right;"></div>
    </div>
    
    <?php foreach($users as $u): 
        $isActive = !($u['locked'] ?? 0); // Annahme: locked=1 bedeutet gesperrt
        $initials = getInitials($u['name'] ?? '', $u['email']);
        $roleColor = getRoleColor($u['role']);
    ?>
    <div class="user-row" style="<?= !$isActive ? 'opacity:0.6; background:#fff5f5;' : '' ?>">
        
        <div style="flex:1; display:flex; align-items:center;">
            <div class="user-avatar" style="background:<?= $roleColor ?>20; color:<?= $roleColor ?>;">
                <?= $initials ?>
            </div>
            <div>
                <div style="font-weight:bold; color:#333;">
                    <?= htmlspecialchars($u['name'] ?: 'Kein Name') ?>
                </div>
                <div style="font-size:0.85em; color:#666;">
                    <?= htmlspecialchars($u['email']) ?>
                </div>
            </div>
        </div>

        <div style="width:150px;">
            <span class="role-badge" style="background:<?= $roleColor ?>;">
                <?= htmlspecialchars($u['role']) ?>
            </span>
        </div>

        <div style="width:100px;">
            <?php if($isActive): ?>
                <span style="color:#28a745; font-size:0.85em; font-weight:bold;">● Aktiv</span>
            <?php else: ?>
                <span style="color:#d9534f; font-size:0.85em; font-weight:bold;">🔒 Gesperrt</span>
            <?php endif; ?>
        </div>

        <div style="width:50px; text-align:right;">
            <a href="?route=user_edit&id=<?= $u['id'] ?>" class="btn-icon btn-sq" title="Bearbeiten">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
  </div>

</section>