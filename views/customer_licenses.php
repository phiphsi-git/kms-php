<?php
use App\Csrf;

// Helper für Ablauf-Farben
function getExpColor($date) {
    if (!$date) return '';
    $ts = strtotime($date);
    if ($ts < time()) return 'color:#d9534f; font-weight:bold;'; // Abgelaufen (Rot)
    if ($ts < time() + (86400 * 90)) return 'color:#e67e22; font-weight:bold;'; // < 90 Tage (Orange)
    return 'color:#28a745;'; // OK (Grün)
}
?>
<section class="dash" style="max-width:1400px; margin:0 auto; padding:20px;">
    
    <header class="dash-header">
        <div class="dash-title">
            <h2>Inventar: <?= htmlspecialchars($c['name']) ?></h2>
            <div class="muted">Software, Lizenzen, Zertifikate & Wartung</div>
        </div>
        <div class="actions">
            <a href="?route=customer_view&id=<?= $c['id'] ?>" class="btn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg> Zurück
            </a>
            <a href="?route=license_export&customer_id=<?= $c['id'] ?>" class="btn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> CSV Export
            </a>
        </div>
    </header>

    <div class="content-card" style="margin-bottom:30px;">
        <div class="card-head" style="background:#f4f7fa; border-bottom:1px solid #e1e4e8;">
            <h3 style="margin:0; font-size:1.1em; color:#333;">Neuen Eintrag erfassen</h3>
        </div>
        <form method="post" action="?route=license_create" enctype="multipart/form-data" style="padding:20px;">
            <?= Csrf::field() ?>
            <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
            
            <div style="display:grid; grid-template-columns: 1fr 2fr 2fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Typ</label>
                    <select name="type" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                        <option value="Software">Software</option>
                        <option value="Lizenz">Lizenz</option>
                        <option value="Zertifikat">Zertifikat</option>
                        <option value="Wartung">Wartung / Carepack</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Bezeichnung *</label>
                    <input type="text" name="software_name" placeholder="z.B. Office 365 Standard" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Hersteller / Händler</label>
                    <input type="text" name="vendor" placeholder="z.B. Microsoft / Digitec" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Anzahl</label>
                    <input type="number" name="seats" value="1" min="0" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Lizenzschlüssel / ID</label>
                    <input type="text" name="license_key" placeholder="XXXX-XXXX-XXXX-XXXX" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-family:monospace;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Gültig ab</label>
                    <input type="date" name="valid_from" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Gültig bis (Ablauf)</label>
                    <input type="date" name="valid_until" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">URL / Link</label>
                    <input type="url" name="url" placeholder="https://admin.microsoft.com" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Datei Upload (z.B. Zertifikat)</label>
                    <input type="file" name="lic_file" style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px; background:#fff;">
                </div>
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:0.85em; font-weight:bold; margin-bottom:5px;">Verknüpfte Systeme</label>
                <select name="system_ids[]" multiple style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; height:80px;">
                    <?php foreach($systems as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small class="muted" style="display:block; margin-top:2px;">Strg + Klick für Mehrfachauswahl</small>
            </div>

            <div style="margin-bottom:20px;">
                <input type="text" name="notes" placeholder="Interne Notizen..." style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
            </div>
            
            <div style="text-align:right;">
                <button type="submit" class="btn-primary" style="padding:10px 25px; border:none; border-radius:4px; cursor:pointer; background:#0056b3; color:#fff;">Eintrag speichern</button>
            </div>
        </form>
    </div>

    <div class="content-card">
        <div class="card-head" style="justify-content:space-between; background:#fff; border-bottom:1px solid #eee; padding:15px;">
            <h3 style="margin:0;">Bestandsliste</h3>
            <input type="text" id="licSearch" placeholder="Liste filtern..." onkeyup="filterLic()" style="padding:6px 10px; border:1px solid #ccc; border-radius:4px; width:200px;">
        </div>
        
        <div style="max-height:800px; overflow-y:auto;">
            <table class="log-table" id="licTable" style="width:100%; border-collapse:collapse; font-size:0.95em;">
                <thead style="background:#f8f9fa; position:sticky; top:0; z-index:10; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <tr>
                        <th style="padding:12px; text-align:left; width:10%;">Typ</th>
                        <th style="padding:12px; text-align:left; width:20%;">Software / Bezeichnung</th>
                        <th style="padding:12px; text-align:left; width:15%;">Hersteller</th>
                        <th style="padding:12px; text-align:left; width:15%;">Gültigkeit</th>
                        <th style="padding:12px; text-align:left; width:20%;">Key / Link</th>
                        <th style="padding:12px; text-align:left; width:15%;">Systeme</th>
                        <th style="padding:12px; text-align:center; width:5%;">Anz.</th>
                        <th style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($licenses)): ?>
                        <tr><td colspan="8" style="padding:20px; text-align:center; color:#888;">Keine Einträge vorhanden.</td></tr>
                    <?php else: ?>
                        <?php foreach($licenses as $l): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:12px; vertical-align:top;">
                                <span style="background:#eee; padding:2px 6px; border-radius:4px; font-size:0.85em; color:#555;">
                                    <?= htmlspecialchars($l['type'] ?? 'Software') ?>
                                </span>
                            </td>

                            <td style="padding:12px; vertical-align:top;">
                                <strong style="color:#333;"><?= htmlspecialchars($l['software_name']) ?></strong>
                                <?php if(!empty($l['file_path'])): ?>
                                    <div style="margin-top:4px;">
                                        <span style="font-size:0.85em; color:#0056b3;">📄 Datei vorhanden</span>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td style="padding:12px; vertical-align:top; color:#555;">
                                <?= htmlspecialchars($l['vendor'] ?? '-') ?>
                            </td>

                            <td style="padding:12px; vertical-align:top;">
                                <?php if(!empty($l['valid_from'])): ?>
                                    <div style="font-size:0.8em; color:#888;">ab <?= date('d.m.Y', strtotime($l['valid_from'])) ?></div>
                                <?php endif; ?>
                                
                                <?php if(!empty($l['valid_until'])): ?>
                                    <div style="<?= getExpColor($l['valid_until']) ?>">
                                        bis <?= date('d.m.Y', strtotime($l['valid_until'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#999;">- Unbefristet -</span>
                                <?php endif; ?>
                            </td>

                            <td style="padding:12px; vertical-align:top;">
                                <?php if(!empty($l['license_key'])): ?>
                                    <div style="font-family:monospace; font-size:0.9em; background:#f9f9f9; padding:2px 4px; border:1px solid #eee; display:inline-block; border-radius:3px; word-break:break-all;">
                                        <?= htmlspecialchars($l['license_key']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if(!empty($l['url'])): ?>
                                    <div style="margin-top:4px;">
                                        <a href="<?= htmlspecialchars($l['url']) ?>" target="_blank" style="color:#0056b3; text-decoration:none; font-size:0.9em;">🔗 Link öffnen</a>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td style="padding:12px; vertical-align:top;">
                                <?php if(!empty($l['system_names'])): ?>
                                    <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                        <?php foreach($l['system_names'] as $sn): ?>
                                            <span style="background:#e3f2fd; color:#0d47a1; padding:2px 5px; border-radius:3px; font-size:0.8em;">
                                                <?= htmlspecialchars($sn) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#ccc;">-</span>
                                <?php endif; ?>
                            </td>

                            <td style="padding:12px; vertical-align:top; text-align:center; font-weight:bold;">
                                <?= (int)($l['seats'] ?? 1) ?>
                            </td>

                            <td style="padding:12px; vertical-align:top; text-align:right;">
                                <a href="?route=license_delete&id=<?= $l['id'] ?>" onclick="return confirm('Eintrag wirklich löschen?')" style="color:#d9534f; text-decoration:none; font-weight:bold; font-size:1.2em;">&times;</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
function filterLic() {
  const filter = document.getElementById('licSearch').value.toLowerCase();
  const rows = document.querySelectorAll('#licTable tbody tr');
  rows.forEach(r => {
      // Ignoriere die "Keine Einträge" Zeile beim Filtern
      if(r.innerText.includes("Keine Einträge")) return;
      
      const text = r.innerText.toLowerCase();
      r.style.display = text.includes(filter) ? '' : 'none';
  });
}
</script>