<?php
use App\Csrf;
use App\Auth;
$user = Auth::user();

// Kleine Statistik laden (Meine offenen Tickets)
$myOpenTasks = \App\DB::pdo()->query("SELECT COUNT(*) FROM tasks t JOIN customers c ON c.id=t.customer_id WHERE t.status IN ('offen','ausstehend') AND (c.responsible_technician_id={$user['id']} OR c.owner_user_id={$user['id']})")->fetchColumn();

// Prüfen ob 2FA aktiv ist
$has2fa = !empty($user['totp_secret']);
?>
<section class="dash" style="max-width:900px; margin:0 auto; padding:20px;">
  
  <header class="dash-header">
    <div class="dash-title">
        <h2>Mein Konto</h2>
        <div class="muted">Angemeldet als <?= htmlspecialchars($user['email']) ?></div>
    </div>
  </header>

  <div style="display:grid; grid-template-columns: 2fr 1fr; gap:25px;">
      
      <div style="display:flex; flex-direction:column; gap:25px;">
        
        <div class="content-card">
            <div class="card-head"><h3>Benutzerdaten</h3></div>
            <div style="padding:20px;">
                <div style="display:grid; grid-template-columns: 120px 1fr; gap:15px; margin-bottom:15px; align-items:center;">
                    <div style="font-weight:bold; color:#555;">Name:</div>
                    <div style="font-size:1.1em;"><?= htmlspecialchars($user['name'] ?? '-') ?></div>
                </div>
                <div style="display:grid; grid-template-columns: 120px 1fr; gap:15px; margin-bottom:15px; align-items:center;">
                    <div style="font-weight:bold; color:#555;">Rolle:</div>
                    <div><span style="background:#e3f2fd; color:#0d47a1; padding:3px 10px; border-radius:12px; font-weight:bold; font-size:0.9em;"><?= htmlspecialchars($user['role']) ?></span></div>
                </div>
                <div style="display:grid; grid-template-columns: 120px 1fr; gap:15px; align-items:center;">
                    <div style="font-weight:bold; color:#555;">Email:</div>
                    <div style="font-family:monospace; color:#333;"><?= htmlspecialchars($user['email']) ?></div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-head" style="background:#fffdf5;"><h3>Passwort ändern</h3></div>
            <form method="post" action="?route=account_password" style="padding:20px;">
                <?= Csrf::field() ?>
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Aktuelles Passwort</label>
                    <input type="password" name="current" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>

                <div style="margin-bottom:15px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Neues Passwort</label>
                    <input type="password" name="new" required minlength="8" autocomplete="new-password" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Wiederholung</label>
                    <input type="password" name="confirm" required minlength="8" autocomplete="new-password" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>

                <div style="text-align:right;">
                    <button type="submit" class="btn-primary" style="padding:10px 20px; border:none; border-radius:4px; cursor:pointer; background:#0056b3; color:white;">Speichern</button>
                </div>
            </form>
        </div>

      </div>

      <div style="display:flex; flex-direction:column; gap:25px;">
          
          <div class="content-card" style="text-align:center; padding:30px 20px;">
              <div style="font-size:3em; font-weight:bold; color:#0275d8; line-height:1;"><?= $myOpenTasks ?></div>
              <div style="color:#777; margin-top:5px; font-weight:bold; text-transform:uppercase; font-size:0.85em;">Meine offenen Tickets</div>
          </div>

          <div class="content-card">
              <div class="card-head"><h3>Einstellungen</h3></div>
              <div style="padding:15px;">
                  
                  <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:15px; margin-bottom:15px; border-bottom:1px solid #eee;">
                      <div>
                          <strong style="display:block;">2-Faktor-Schutz</strong>
                          <?php if($has2fa): ?>
                              <small style="color:#28a745; font-weight:bold;">● Aktiviert</small>
                          <?php else: ?>
                              <small class="muted">Nicht eingerichtet</small>
                          <?php endif; ?>
                      </div>
                      <a href="?route=account_2fa" class="btn-primary" style="padding:6px 12px; font-size:0.85em; text-decoration:none; background:<?= $has2fa ? '#28a745' : '#0056b3' ?>; color:white; border-radius:4px;">
                          <?= $has2fa ? 'Verwalten' : 'Einrichten' ?>
                      </a>
                  </div>

                  <div style="display:flex; justify-content:space-between; align-items:center; padding:5px 0;">
                      <div>
                          <strong style="display:block; color:#ccc;">Dark Mode</strong>
                          <small class="muted">Kommt bald...</small>
                      </div>
                      <label style="cursor:not-allowed; opacity:0.5;">
                          <input type="checkbox" disabled style="transform:scale(1.2);">
                      </label>
                  </div>

              </div>
          </div>

      </div>

  </div>
</section>