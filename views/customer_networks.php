<?php
use App\Csrf;

// Gruppierung nach Standort vorbereiten
$sites = [];
if (!empty($networks)) {
    foreach($networks as $n) { 
        $site = $n['site_name'] ?: 'Hauptstandort';
        $sites[$site][] = $n; 
    }
}
?>
<section class="dash" style="max-width:1400px; margin:0 auto; padding:20px;">
    
    <header class="dash-header">
        <div class="dash-title">
            <h2>Netzwerk-Planer: <?= htmlspecialchars($c['name']) ?></h2>
            <div class="muted">Standorte, VLANs, Subnetze & Gateways</div>
        </div>
        <div class="actions">
            <a href="?route=customer_view&id=<?= $c['id'] ?>" class="btn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg> Zurück
            </a>
        </div>
    </header>

    <div class="content-card" style="margin-bottom:30px;">
        <div class="card-head" style="background:#f4f7fa; border-bottom:1px solid #e1e4e8;">
            <h3 style="margin:0; font-size:1.1em; color:#333;">Netzwerk / VLAN hinzufügen</h3>
        </div>
        <form method="post" action="?route=network_create" style="padding:20px;">
            <?= Csrf::field() ?>
            <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Standort</label>
                    <input type="text" name="site_name" placeholder="z.B. Hauptsitz" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Bezeichnung (Name)</label>
                    <input type="text" name="name" placeholder="z.B. VoIP / Gäste" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">VLAN ID</label>
                    <input type="number" name="vlan_id" placeholder="z.B. 20" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
            </div>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Subnetz (CIDR)</label>
                    <input type="text" name="subnet" placeholder="192.168.10.0/24" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-family:monospace;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Gateway IP</label>
                    <input type="text" name="gateway" placeholder="192.168.10.1" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-family:monospace;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">DHCP Bereich</label>
                    <input type="text" name="dhcp_range" placeholder=".50 - .200" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">DNS Server</label>
                    <input type="text" name="dns_servers" placeholder="192.168.10.1, 8.8.8.8" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <input type="text" name="notes" placeholder="Bemerkungen..." style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
            </div>
            
            <div style="text-align:right;">
                <button type="submit" class="btn-primary" style="padding:10px 25px; border:none; border-radius:4px; cursor:pointer; background:#0056b3; color:#fff;">Netzwerk anlegen</button>
            </div>
        </form>
    </div>

    <?php if (empty($sites)): ?>
        <div class="content-card" style="padding:40px; text-align:center; color:#888;">
            Noch keine Netzwerke erfasst.
        </div>
    <?php else: ?>
        <?php foreach($sites as $siteName => $nets): ?>
        <div class="content-card" style="margin-bottom:30px;">
            <div class="card-head" style="background:#e3f2fd; color:#0d47a1; border-bottom:1px solid #bbdefb; padding:12px 20px;">
                <h3 style="margin:0; display:flex; align-items:center; gap:10px; font-size:1.1em;">
                    📍 <?= htmlspecialchars($siteName) ?>
                </h3>
            </div>
            
            <table style="width:100%; border-collapse:collapse; font-size:0.95em;">
                <thead>
                    <tr style="background:#f8f9fa; border-bottom:2px solid #eee; color:#555;">
                        <th style="text-align:left; padding:12px; width:8%;">VLAN</th>
                        <th style="text-align:left; padding:12px; width:15%;">Name</th>
                        <th style="text-align:left; padding:12px; width:15%;">Subnetz</th>
                        <th style="text-align:left; padding:12px; width:15%;">Gateway</th>
                        <th style="text-align:left; padding:12px; width:20%;">DHCP / DNS</th>
                        <th style="text-align:left; padding:12px; width:22%;">Notiz</th>
                        <th style="width:5%;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($nets as $n): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px; font-weight:bold; color:#555;">
                            <?= $n['vlan_id'] ? 'VLAN '.$n['vlan_id'] : '-' ?>
                        </td>
                        
                        <td style="padding:12px;">
                            <strong style="font-size:1.05em;"><?= htmlspecialchars($n['name']) ?></strong>
                        </td>
                        
                        <td style="padding:12px;">
                            <span style="font-family:monospace; background:#fafafa; padding:2px 4px; border:1px solid #eee; border-radius:3px;">
                                <?= htmlspecialchars($n['subnet']) ?>
                            </span>
                        </td>
                        
                        <td style="padding:12px; font-family:monospace;">
                            <?= htmlspecialchars($n['gateway']) ?>
                        </td>
                        
                        <td style="padding:12px; font-size:0.9em; line-height:1.4;">
                            <?php if($n['dhcp_range']): ?>
                                <div style="color:#333;">DHCP: <?= htmlspecialchars($n['dhcp_range']) ?></div>
                            <?php endif; ?>
                            <?php if($n['dns_servers']): ?>
                                <div style="color:#666;">DNS: <?= htmlspecialchars($n['dns_servers']) ?></div>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding:12px; color:#777; font-style:italic;">
                            <?= htmlspecialchars($n['notes']) ?>
                        </td>
                        
                        <td style="padding:12px; text-align:right;">
                            <a href="?route=network_delete&id=<?= $n['id'] ?>" onclick="return confirm('Netzwerk wirklich löschen?')" style="color:#d9534f; text-decoration:none; font-weight:bold; font-size:1.2em;">&times;</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</section>