<?php 
function getActionStyle($act) {
    if(in_array($act,['delete','remove'])) return 'background:#ffebee; color:#c62828;';
    if(in_array($act,['create','add','upload'])) return 'background:#e8f5e9; color:#2e7d32;';
    return 'background:#e3f2fd; color:#1565c0;';
}
?>
<section class="dash" style="max-width:1200px; margin:0 auto; padding:20px;">
    <header class="dash-header" style="display:flex; justify-content:space-between; margin-bottom:20px;">
        <h2 style="margin:0;">Audit Log</h2>
        <a href="?route=dashboard" class="btn-icon">Dashboard</a>
    </header>
    <div class="content-card" style="background:#fff; border:1px solid #ddd; border-radius:8px; overflow:hidden;">
        <div style="padding:15px; background:#f9f9f9; border-bottom:1px solid #eee; display:flex; justify-content:space-between;">
            <strong>Systemweite Aktivitäten</strong>
            <form style="display:flex; gap:10px;">
                <input type="hidden" name="route" value="audit_log">
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Suchen..." style="padding:5px;">
                <button type="submit">Suchen</button>
            </form>
        </div>
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr style="background:#fff; text-align:left;"><th style="padding:10px;">Zeit</th><th style="padding:10px;">User</th><th style="padding:10px;">Kunde</th><th style="padding:10px;">Aktion</th><th style="padding:10px;">Details</th></tr></thead>
            <tbody>
                <?php foreach($logs as $l): ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:10px; color:#666; font-size:0.9em;"><?= date('d.m.y H:i', strtotime($l['created_at'])) ?></td>
                    <td style="padding:10px;"><strong><?= htmlspecialchars($l['user_name']?:$l['user_email']) ?></strong></td>
                    <td style="padding:10px;"><a href="?route=customer_view&id=<?= $l['customer_id'] ?>"><?= htmlspecialchars($l['customer_name']) ?></a></td>
                    <td style="padding:10px;"><span style="padding:2px 6px; border-radius:4px; font-size:0.8em; font-weight:bold; <?= getActionStyle($l['action_type']) ?>"><?= strtoupper($l['action_type']) ?></span></td>
                    <td style="padding:10px; color:#444;"><?= htmlspecialchars($l['note']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>