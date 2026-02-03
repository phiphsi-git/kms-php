<?php use App\Csrf; ?>
<section class="dash" style="max-width:600px; margin:0 auto; padding:20px;">
    <header class="dash-header">
        <h2>2-Faktor-Authentifizierung (2FA)</h2>
        <a href="?route=account" class="btn-icon">Zurück</a>
    </header>

    <div class="card" style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:8px;">
        <?php if($enabled): ?>
            <div style="text-align:center; color:#28a745; margin-bottom:20px;">
                <h3 style="margin:0;">✅ 2FA ist Aktiviert</h3>
                <p>Ihr Konto ist geschützt.</p>
            </div>
            <form method="post" action="?route=account_2fa_disable" onsubmit="return confirm('Sind Sie sicher? Ihr Konto ist dann weniger geschützt.');">
                <?= Csrf::field() ?>
                <button type="submit" class="btn-danger" style="width:100%; padding:10px;">2FA Deaktivieren</button>
            </form>
        <?php else: ?>
            <p>Scannen Sie diesen QR-Code mit <strong>Google Authenticator</strong> oder <strong>Microsoft Authenticator</strong>.</p>
            
            <div style="text-align:center; margin:20px 0;">
                <img src="https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=<?= urlencode($otpUrl) ?>" style="border:1px solid #ccc;">
                <div style="margin-top:10px; font-family:monospace; background:#f9f9f9; padding:5px;">
                    Secret: <?= $secret ?>
                </div>
            </div>

            <form method="post" action="?route=account_2fa_enable">
                <?= Csrf::field() ?>
                <input type="hidden" name="secret" value="<?= $secret ?>">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Bestätigungscode:</label>
                <div style="display:flex; gap:10px;">
                    <input type="text" name="code" placeholder="123 456" required style="flex:1; padding:10px;">
                    <button type="submit" class="btn-primary" style="padding:10px 20px;">Aktivieren</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>