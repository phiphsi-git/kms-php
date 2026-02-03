<?php use App\Csrf; ?>
<section class="dash" style="max-width:800px; margin:0 auto; padding:20px;">
  <header style="margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">
    <h2 style="margin:0;">Kunde bearbeiten: <?= htmlspecialchars($c['name']) ?></h2>
  </header>

  <form method="post" action="?route=customer_update&id=<?= $c['id'] ?>" class="card" style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:8px;">
    <?= Csrf::field() ?>
    
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
        <div><label style="display:block; font-weight:bold; margin-bottom:5px;">Firmenname</label><input type="text" name="name" value="<?= htmlspecialchars($c['name']) ?>" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;"></div>
        <div><label style="display:block; font-weight:bold; margin-bottom:5px;">Webseite</label><input type="text" name="website" value="<?= htmlspecialchars($c['website']??'') ?>" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;"></div>
    </div>

    <div style="margin-bottom:20px;"><label style="display:block; font-weight:bold; margin-bottom:5px;">Adresse</label><input type="text" name="street" placeholder="Strasse" value="<?= htmlspecialchars($c['street']??'') ?>" style="width:100%; margin-bottom:5px; padding:8px; border:1px solid #ccc; border-radius:4px;"><div style="display:flex; gap:10px;"><input type="text" name="zip" placeholder="PLZ" value="<?= htmlspecialchars($c['zip']??'') ?>" style="width:80px; padding:8px; border:1px solid #ccc; border-radius:4px;"><input type="text" name="city" placeholder="Ort" value="<?= htmlspecialchars($c['city']??'') ?>" style="flex:1; padding:8px; border:1px solid #ccc; border-radius:4px;"></div></div>
    <div style="margin-bottom:20px;"><label style="display:block; font-weight:bold; margin-bottom:5px;">Logo URL</label><input type="text" name="logo_url" value="<?= htmlspecialchars($c['logo_url']??'') ?>" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;"></div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px; background:#f9f9f9; padding:15px; border-radius:6px;">
        <div>
            <label style="display:block; font-weight:bold; margin-bottom:5px;">Fernzugriff Methode</label>
            <select name="remote_access_type" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                <option value="">- Keiner -</option>
                <option value="anydesk" <?= ($c['remote_access_type']??'')==='anydesk'?'selected':'' ?>>AnyDesk</option>
                <option value="citrix" <?= ($c['remote_access_type']??'')==='citrix'?'selected':'' ?>>Citrix</option>
                <option value="forticlient" <?= ($c['remote_access_type']??'')==='forticlient'?'selected':'' ?>>FortiClient VPN</option>
                <option value="fritzbox" <?= ($c['remote_access_type']??'')==='fritzbox'?'selected':'' ?>>FritzBox</option>
                <option value="microsoft" <?= ($c['remote_access_type']??'')==='microsoft'?'selected':'' ?>>Microsoft (RDP/VPN)</option>
                <option value="openvpn" <?= ($c['remote_access_type']??'')==='openvpn'?'selected':'' ?>>OpenVPN</option>
                <option value="rdp" <?= ($c['remote_access_type']??'')==='rdp'?'selected':'' ?>>RDP (Generisch)</option>
                <option value="sophos" <?= ($c['remote_access_type']??'')==='sophos'?'selected':'' ?>>Sophos VPN</option>
                <option value="ssh" <?= ($c['remote_access_type']??'')==='ssh'?'selected':'' ?>>SSH</option>
                <option value="swisscom_ras" <?= ($c['remote_access_type']??'')==='swisscom_ras'?'selected':'' ?>>Swisscom RAS</option>
                <option value="swisscom_pex" <?= ($c['remote_access_type']??'')==='swisscom_pex'?'selected':'' ?>>Swisscom PEX</option>
                <option value="teamviewer" <?= ($c['remote_access_type']??'')==='teamviewer'?'selected':'' ?>>TeamViewer</option>
                <option value="unifi_ui" <?= ($c['remote_access_type']??'')==='unifi_ui'?'selected':'' ?>>UniFi UI</option>
                <option value="unifi_wifiman" <?= ($c['remote_access_type']??'')==='unifi_wifiman'?'selected':'' ?>>UniFi Wifiman</option>
                <option value="wireguard" <?= ($c['remote_access_type']??'')==='wireguard'?'selected':'' ?>>WireGuard</option>
            </select>
        </div>
        <div>
            <?php if (\App\Policy::hasRole(['Admin','Projektleiter','LeitenderTechniker','Techniker'])): ?>
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Passwort-Manager Link</label>
                <input type="url" name="password_manager_url" placeholder="https://..." value="<?= htmlspecialchars($c['password_manager_url'] ?? '') ?>" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
            <?php endif; ?>
        </div>
    </div>

    <h4 style="margin-top:0; color:#555;">Wartungsintervall & Planung</h4>
    <div style="background:#f4f7fa; padding:15px; border-radius:6px; border:1px solid #e1e4e8; margin-bottom:20px;">
        <div style="margin-bottom:15px;">
            <label style="display:block; font-weight:bold; margin-bottom:5px;">Turnus</label>
            <select name="maintenance_type" id="maintenance_type" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                <option value="none" <?= ($c['maintenance_type']??'none')==='none'?'selected':'' ?>>-- Keine automatische Wartung --</option>
                <option value="daily" <?= ($c['maintenance_type']??'')==='daily'?'selected':'' ?>>Täglich (Daily)</option>
                <option value="weekly" <?= ($c['maintenance_type']??'')==='weekly'?'selected':'' ?>>Wöchentlich</option>
                <option value="monthly" <?= ($c['maintenance_type']??'')==='monthly'?'selected':'' ?>>Monatlich</option>
                <option value="yearly" <?= ($c['maintenance_type']??'')==='yearly'?'selected':'' ?>>Jährlich</option>
                <option value="paused" <?= ($c['maintenance_type']??'')==='paused'?'selected':'' ?>>PAUSIERT</option>
            </select>
        </div>
        <div id="m_settings" style="display:none; grid-template-columns: 1fr 1fr; gap:15px;">
            <div id="grp_time"><label style="display:block; font-size:0.9em; font-weight:bold; margin-bottom:3px;">Startzeit</label><input type="time" name="maintenance_time" value="<?= htmlspecialchars($c['maintenance_time']??'08:00') ?>" style="width:100%; padding:6px; border:1px solid #ccc; border-radius:4px;"></div>
            <div id="grp_weekday" style="display:none;"><label style="display:block; font-size:0.9em; font-weight:bold; margin-bottom:3px;">Wochentag</label><select name="maintenance_weekday" style="width:100%; padding:6px; border:1px solid #ccc; border-radius:4px;"><?php $days = [1=>'Montag',2=>'Dienstag',3=>'Mittwoch',4=>'Donnerstag',5=>'Freitag',6=>'Samstag',7=>'Sonntag']; foreach($days as $k=>$v): ?><option value="<?= $k ?>" <?= ($c['maintenance_weekday']??1)==$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
            <div id="grp_weekofmonth" style="display:none;"><label style="display:block; font-size:0.9em; font-weight:bold; margin-bottom:3px;">Woche im Monat</label><select name="maintenance_week_of_month" style="width:100%; padding:6px; border:1px solid #ccc; border-radius:4px;"><option value="1" <?= ($c['maintenance_week_of_month']??1)==1?'selected':'' ?>>Erste Woche</option><option value="2" <?= ($c['maintenance_week_of_month']??1)==2?'selected':'' ?>>Zweite Woche</option><option value="3" <?= ($c['maintenance_week_of_month']??1)==3?'selected':'' ?>>Dritte Woche</option><option value="4" <?= ($c['maintenance_week_of_month']??1)==4?'selected':'' ?>>Vierte Woche</option><option value="5" <?= ($c['maintenance_week_of_month']??1)==5?'selected':'' ?>>Letzte Woche</option></select></div>
            <div id="grp_year" style="display:none;"><label style="display:block; font-size:0.9em; font-weight:bold; margin-bottom:3px;">Datum (Monat / Tag)</label><div style="display:flex; gap:5px;"><select name="maintenance_year_month" style="width:60%;"><?php for($m=1; $m<=12; $m++): ?><option value="<?= $m ?>" <?= ($c['maintenance_year_month']??1)==$m?'selected':'' ?>><?= date('F', mktime(0,0,0,$m,10)) ?></option><?php endfor; ?></select><input type="number" name="maintenance_year_day" min="1" max="31" value="<?= htmlspecialchars($c['maintenance_year_day']??1) ?>" style="width:40%; padding:6px; border:1px solid #ccc; border-radius:4px;"></div></div>
        </div>
        <div id="grp_pause" style="display:none; margin-top:10px;"><label style="display:block; font-size:0.9em; font-weight:bold; margin-bottom:3px;">Grund für Pause</label><input type="text" name="maintenance_pause_reason" value="<?= htmlspecialchars($c['maintenance_pause_reason']??'') ?>" placeholder="Z.B. Betriebsurlaub, Umbau..." style="width:100%; padding:6px; border:1px solid #ccc; border-radius:4px; color:#d9534f;"></div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
        <div><label style="display:block; font-weight:bold; margin-bottom:5px;">Zuständiger Techniker</label><select name="responsible_technician_id" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;"><option value="0">- Keiner -</option><?php foreach ($technicians as $t): ?><option value="<?= $t['id'] ?>" <?= ($c['tech_id']??0) == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name'] ?? $t['email'] ?? 'User #'.$t['id']) ?></option><?php endforeach; ?></select></div>
        <div><label style="display:block; font-weight:bold; margin-bottom:5px;">Kundenverantwortlicher (Intern)</label><select name="owner_user_id" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;"><option value="0">- Keiner -</option><?php foreach ($employees as $e): ?><option value="<?= $e['id'] ?>" <?= ($c['owner_id']??0) == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['name'] ?? $e['email'] ?? 'User #'.$e['id']) ?></option><?php endforeach; ?></select></div>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
        <a href="?route=customer_view&id=<?= $c['id'] ?>" style="padding:10px 20px; border:1px solid #ccc; text-decoration:none; color:#333; border-radius:4px;">Abbrechen</a>
        <button type="submit" style="padding:10px 20px; background:#0056b3; color:white; border:none; border-radius:4px; cursor:pointer;">Speichern</button>
    </div>
  </form>
</section>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('maintenance_type');
    const settingsBox = document.getElementById('m_settings');
    const grpTime = document.getElementById('grp_time');
    const grpWeekday = document.getElementById('grp_weekday');
    const grpWeekOfMonth = document.getElementById('grp_weekofmonth');
    const grpYear = document.getElementById('grp_year');
    const grpPause = document.getElementById('grp_pause');

    function updateVisibility() {
        const val = typeSelect.value;
        settingsBox.style.display = 'none'; grpTime.style.display = 'none'; grpWeekday.style.display = 'none'; grpWeekOfMonth.style.display = 'none'; grpYear.style.display = 'none'; grpPause.style.display = 'none';
        if (val === 'none') return;
        if (val === 'paused') { grpPause.style.display = 'block'; return; }
        settingsBox.style.display = 'grid';
        if (val !== 'yearly') grpTime.style.display = 'block';
        if (val === 'weekly') grpWeekday.style.display = 'block';
        else if (val === 'monthly') { grpWeekday.style.display = 'block'; grpWeekOfMonth.style.display = 'block'; }
        else if (val === 'yearly') grpYear.style.display = 'block';
    }
    typeSelect.addEventListener('change', updateVisibility);
    updateVisibility();
});
</script>