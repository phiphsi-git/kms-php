<?php
$stats = \App\DashboardRepo::getStats();
$user = \App\Auth::user();
?>
<section class="dash" style="max-width:1200px; margin:0 auto;">
    
    <h1 style="margin-bottom:30px;">Cockpit <small style="font-weight:normal; font-size:0.5em; color:#777;">Hallo, <?= htmlspecialchars($user['email']) ?></small></h1>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:40px;">
        
        <a href="?route=tasks_global&filter=overdue" class="card" style="text-decoration:none; background:#fff; padding:20px; border-radius:8px; border:1px solid #ddd; border-left:5px solid #d9534f; box-shadow:0 2px 5px rgba(0,0,0,0.05); transition:transform 0.2s;">
            <div style="font-size:0.9em; color:#777; text-transform:uppercase;">Überfällig / Heute</div>
            <div style="font-size:2.5em; font-weight:bold; color:#d9534f; margin-top:5px;"><?= $stats['overdue'] ?></div>
        </a>

        <a href="?route=tasks_global&filter=all" class="card" style="text-decoration:none; background:#fff; padding:20px; border-radius:8px; border:1px solid #ddd; border-left:5px solid #0275d8; box-shadow:0 2px 5px rgba(0,0,0,0.05); transition:transform 0.2s;">
            <div style="font-size:0.9em; color:#777; text-transform:uppercase;">Alle Offenen Tickets</div>
            <div style="font-size:2.5em; font-weight:bold; color:#333; margin-top:5px;"><?= $stats['open_total'] ?></div>
        </a>

        <a href="?route=tasks_global&filter=mine" class="card" style="text-decoration:none; background:#fff; padding:20px; border-radius:8px; border:1px solid #ddd; border-left:5px solid #5cb85c; box-shadow:0 2px 5px rgba(0,0,0,0.05); transition:transform 0.2s;">
            <div style="font-size:0.9em; color:#777; text-transform:uppercase;">Meine Tickets</div>
            <div style="font-size:2.5em; font-weight:bold; color:#333; margin-top:5px;"><?= $stats['my_open'] ?></div>
        </a>

    </div>

    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px;">
        
        <div class="card" style="background:#fff; border-radius:8px; border:1px solid #eee; overflow:hidden; display:flex; flex-direction:column;">
            <div style="background:#f9f9f9; padding:10px 15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-weight:bold;">Neueste Aktivitäten (Global)</span>
                <input type="text" id="dashLogSearch" placeholder="Filter..." onkeyup="filterDashLog()" style="padding:5px 10px; border:1px solid #ccc; border-radius:4px; font-size:0.9em; width:150px;">
            </div>
            <div style="max-height:350px; overflow-y:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.9em;" id="dashLogTable">
                    <?php if(empty($stats['logs'])): ?>
                        <tr><td style="padding:20px; text-align:center; color:#888;">Keine Aktivitäten vorhanden.</td></tr>
                    <?php else: ?>
                        <?php foreach($stats['logs'] as $l): ?>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:12px; color:#888; width:130px; font-size:0.85em; vertical-align:top;"><?= date('d.m.y H:i', strtotime($l['created_at'])) ?></td>
                            <td style="padding:12px; vertical-align:top;">
                                <?php if(!empty($l['customer_name'])): ?>
                                    <a href="?route=customer_view&id=<?= $l['customer_id'] ?>" style="text-decoration:none; font-weight:bold; color:#333;"><?= htmlspecialchars($l['customer_name']) ?></a><br>
                                <?php endif; ?>
                                <span style="color:#555;"><?= htmlspecialchars($l['note']) ?></span>
                            </td>
                            <td style="padding:12px; text-align:right; vertical-align:top;"><span style="background:#f0f0f0; padding:2px 6px; border-radius:4px; font-size:0.75em; color:#666;"><?= htmlspecialchars($l['user_email']??'System') ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:30px;">
            <div class="card" style="background:#fff; border-radius:8px; border:1px solid #eee;">
                <div style="background:#f9f9f9; padding:15px; border-bottom:1px solid #eee; font-weight:bold;">Top Arbeitslast</div>
                <ul style="list-style:none; padding:0; margin:0;">
                    <?php if(empty($stats['burners'])): ?><li style="padding:20px; text-align:center; color:#888;">Alles ruhig.</li><?php else: ?>
                        <?php foreach($stats['burners'] as $b): ?>
                        <li style="border-bottom:1px solid #f0f0f0; padding:12px; display:flex; justify-content:space-between; align-items:center;"><a href="?route=customer_view&id=<?= $b['id'] ?>" style="text-decoration:none; font-weight:bold; color:#0275d8;"><?= htmlspecialchars($b['name']) ?></a><span style="background:#f0ad4e; color:white; padding:2px 8px; border-radius:10px; font-weight:bold; font-size:0.9em;"><?= $b['cnt'] ?> Offen</span></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="card" style="background:#fff; border-radius:8px; border:1px solid #eee;">
                <div style="background:#f9f9f9; padding:15px; border-bottom:1px solid #eee; font-weight:bold; color:#d9534f;">Garantie Ablauf (90 Tage)</div>
                <ul style="list-style:none; padding:0; margin:0;">
                    <?php if(empty($stats['expiring'])): ?><li style="padding:20px; text-align:center; color:#888;">Keine.</li><?php else: ?>
                        <?php foreach($stats['expiring'] as $ex): ?>
                        <li style="border-bottom:1px solid #f0f0f0; padding:12px;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                                <a href="?route=system_edit&id=<?= $ex['id'] ?>" style="text-decoration:none; font-weight:bold; color:#333;"><?= htmlspecialchars($ex['name']) ?></a>
                                <span style="color:#d9534f; font-weight:bold; font-size:0.9em;"><?= date('d.m.Y', strtotime($ex['warranty_expires'])) ?></span>
                            </div>
                            <div style="font-size:0.85em; color:#666;"><?= htmlspecialchars($ex['customer_name']) ?></div>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

    </div>
</section>
<script>
function filterDashLog() {
  const input = document.getElementById('dashLogSearch');
  const filter = input.value.toLowerCase();
  const rows = document.querySelectorAll('#dashLogTable tr');
  rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      if (text.includes("keine aktivitäten")) return;
      row.style.display = text.includes(filter) ? '' : 'none';
  });
}
</script>
<style>.card:hover { transform: translateY(-2px); }</style>