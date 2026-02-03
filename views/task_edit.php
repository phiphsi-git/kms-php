<?php
use App\Csrf;
use App\TaskRepo;
use App\SystemRepo;
use App\TaskCheckpointRepo;

// ID prüfen: 0 bedeutet "Neu anlegen"
$id = (int)($_GET['id'] ?? 0);
$customerId = (int)($_GET['customer_id'] ?? 0);
$isNew = ($id === 0);

$t = null;
$checkpoints = [];

if ($isNew) {
    // Standardwerte für neue Aufgabe
    if ($customerId <= 0) die("Fehler: Kein Kunde angegeben.");
    $t = [
        'id' => 0,
        'customer_id' => $customerId,
        'title' => '',
        'status' => 'offen',
        'system_id' => 0,
        'due_date' => null,
        'is_recurring' => 0,
        'is_paused' => 0,
        'pause_reason' => '',
        'comment' => '',
        'time_spent_minutes' => 0
    ];
} else {
    // Bestehende Aufgabe laden
    $t = TaskRepo::find($id);
    if (!$t) die("Aufgabe nicht gefunden.");
    $customerId = $t['customer_id'];
    // Checkpoints nur laden, wenn Aufgabe existiert
    if (class_exists('\App\TaskCheckpointRepo')) {
        $checkpoints = TaskCheckpointRepo::listByTask($id);
    }
}

// Systeme für Dropdown laden
$systems = SystemRepo::listByCustomer($customerId);
?>

<section class="dash" style="max-width:900px; margin:0 auto; padding:20px;">
  
  <header style="margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="margin:0;"><?= $isNew ? 'Neue Aufgabe erstellen' : 'Aufgabe bearbeiten' ?></h2>
    <?php if(!$isNew): ?>
        <div class="muted">Erstellt am <?= date('d.m.Y', strtotime($t['created_at'])) ?></div>
    <?php endif; ?>
  </header>

  <form method="post" action="?route=<?= $isNew ? 'task_create' : 'task_update&id='.$t['id'] ?>" class="card" style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:8px;">
    <?= Csrf::field() ?>
    <?php if($isNew): ?><input type="hidden" name="customer_id" value="<?= $customerId ?>"><?php endif; ?>
    
    <div style="margin-bottom:20px;">
        <label style="display:block; font-weight:bold; margin-bottom:5px;">Titel</label>
        <input type="text" name="title" value="<?= htmlspecialchars($t['title']) ?>" required placeholder="Was ist zu tun?" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:1.1em;">
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
        <div>
            <label style="display:block; font-weight:bold; margin-bottom:5px;">System</label>
            <select name="system_id" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                <option value="0">-- Allgemein (Kein System) --</option>
                <?php foreach ($systems as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($t['system_id'] == $s['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="display:block; font-weight:bold; margin-bottom:5px;">Status</label>
            <select name="status" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-weight:bold;">
                <option value="offen" <?= $t['status']==='offen'?'selected':'' ?> style="color:#d9534f;">Offen</option>
                <option value="ausstehend" <?= $t['status']==='ausstehend'?'selected':'' ?> style="color:#f0ad4e;">Ausstehend</option>
                <option value="erledigt" <?= $t['status']==='erledigt'?'selected':'' ?> style="color:#28a745;">Erledigt</option>
            </select>
        </div>
    </div>

    <div style="margin-bottom:20px; padding:15px; background:#f0f7ff; border:1px solid #cce5ff; border-radius:6px;">
        <label style="display:block; font-weight:bold; margin-bottom:5px; color:#004085;">Zeitaufwand (Minuten)</label>
        <div style="display:flex; align-items:center; gap:10px;">
            <input type="number" name="time_spent_minutes" value="<?= (int)($t['time_spent_minutes']??0) ?>" min="0" style="width:120px; padding:8px; border:1px solid #ccc; border-radius:4px;">
            <small style="color:#666;">Effektive Arbeitszeit erfassen.</small>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
        <div>
            <label style="display:block; font-weight:bold; margin-bottom:5px;">Fälligkeitsdatum</label>
            <input type="datetime-local" name="due_date" value="<?= !empty($t['due_date']) ? date('Y-m-d\TH:i', strtotime($t['due_date'])) : '' ?>" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
        </div>
        
        <div style="display:flex; flex-direction:column; gap:10px; padding-top:25px;">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                <input type="checkbox" name="is_recurring" value="1" <?= !empty($t['is_recurring']) ? 'checked' : '' ?>>
                <span>Wiederkehrende Aufgabe (Kunden-Intervall)</span>
            </label>

            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                <input type="checkbox" name="is_paused" value="1" <?= !empty($t['is_paused']) ? 'checked' : '' ?> onchange="document.getElementById('pause_reason_box').style.display = this.checked ? 'block' : 'none'">
                <span>Aufgabe pausieren</span>
            </label>
            
            <div id="pause_reason_box" style="display:<?= !empty($t['is_paused']) ? 'block' : 'none' ?>; margin-left:25px;">
                <input type="text" name="pause_reason" value="<?= htmlspecialchars($t['pause_reason']??'') ?>" placeholder="Grund für Pause..." style="width:100%; padding:6px; border:1px solid #d9534f; border-radius:4px;">
            </div>
        </div>
    </div>

    <div style="margin-bottom:20px;">
        <label style="display:block; font-weight:bold; margin-bottom:5px;">Kommentar / Notizen</label>
        <textarea name="comment" rows="3" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;"><?= htmlspecialchars($t['comment']??'') ?></textarea>
    </div>

    <?php if(!$isNew): ?>
        <hr style="border:0; border-top:1px solid #eee; margin:30px 0;">
        <h3 style="font-size:1.1em; margin-bottom:15px;">Checkliste / Kontrollpunkte</h3>
        
        <div id="cp-container">
            <?php foreach($checkpoints as $cp): ?>
            <div class="cp-row" style="display:flex; gap:10px; align-items:flex-start; margin-bottom:10px; padding:10px; background:#f9f9f9; border-radius:4px;">
                <input type="hidden" name="cp[ids][]" value="<?= $cp['id'] ?>">
                <input type="hidden" name="cp[orders][]" value="<?= $cp['sort_order'] ?>">
                
                <div style="padding-top:5px;">
                    <input type="checkbox" name="cp[is_done][]" value="1" <?= $cp['is_done']?'checked':'' ?> style="transform:scale(1.2);">
                </div>
                
                <div style="flex:1;">
                    <input type="text" name="cp[labels][]" value="<?= htmlspecialchars($cp['label']) ?>" placeholder="Bezeichnung..." required style="width:100%; padding:6px; border:1px solid #ccc; border-radius:4px; font-weight:bold;">
                    <div style="margin-top:5px; display:flex; gap:10px; align-items:center;">
                        <input type="text" name="cp[comments][]" value="<?= htmlspecialchars($cp['comment']??'') ?>" placeholder="Kommentar / Ergebnis..." style="flex:1; padding:6px; border:1px solid #ddd; border-radius:4px; font-size:0.9em;">
                        <label style="font-size:0.85em; color:#666; display:flex; align-items:center; gap:4px;">
                            <input type="checkbox" name="cp[require_comment_on_fail][]" value="1" <?= $cp['require_comment_on_fail']?'checked':'' ?>>
                            Pflicht bei Fehler
                        </label>
                    </div>
                </div>

                <button type="button" class="btn-icon btn-danger" onclick="this.closest('.cp-row').remove()" title="Entfernen" style="border:none; background:none; cursor:pointer; color:#d9534f;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="addCheckpoint()" style="background:#eee; border:1px solid #ccc; padding:6px 12px; border-radius:4px; cursor:pointer; font-size:0.9em; display:flex; align-items:center; gap:5px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Neuen Punkt hinzufügen
        </button>
    <?php else: ?>
        <div style="margin-top:30px; padding:15px; background:#fff3cd; border:1px solid #ffeeba; color:#856404; border-radius:4px;">
            ℹ️ <strong>Hinweis:</strong> Checklisten können erst erstellt werden, nachdem die Aufgabe einmal gespeichert wurde.
        </div>
    <?php endif; ?>

    <div style="display:flex; justify-content:space-between; margin-top:30px; border-top:1px solid #eee; padding-top:20px;">
        <a href="?route=customer_view&id=<?= $customerId ?>" style="padding:10px 20px; border:1px solid #ccc; text-decoration:none; color:#333; border-radius:4px;">Abbrechen</a>
        <button type="submit" style="padding:10px 25px; background:#0056b3; color:white; border:none; border-radius:4px; cursor:pointer; font-size:1em;">
            <?= $isNew ? 'Aufgabe erstellen' : 'Speichern' ?>
        </button>
    </div>

  </form>
</section>

<?php if(!$isNew): ?>
<template id="cp-template">
    <div class="cp-row" style="display:flex; gap:10px; align-items:flex-start; margin-bottom:10px; padding:10px; background:#f9f9f9; border-radius:4px; border-left:3px solid #0056b3;">
        <input type="hidden" name="cp[ids][]" value="0">
        <input type="hidden" name="cp[orders][]" value="99">
        <div style="padding-top:5px;"><input type="checkbox" disabled title="Erst nach Speichern verfügbar"></div>
        <div style="flex:1;">
            <input type="text" name="cp[labels][]" placeholder="Bezeichnung der Prüfung..." required style="width:100%; padding:6px; border:1px solid #ccc; border-radius:4px; font-weight:bold;">
            <div style="margin-top:5px; display:flex; gap:10px; align-items:center;">
                <input type="text" name="cp[comments][]" placeholder="Kommentar..." style="flex:1; padding:6px; border:1px solid #ddd; border-radius:4px; font-size:0.9em;">
                <label style="font-size:0.85em; color:#666; display:flex; align-items:center; gap:4px;"><input type="checkbox" name="cp[require_comment_on_fail][]" value="1"> Pflicht bei Fehler</label>
            </div>
        </div>
        <button type="button" class="btn-icon btn-danger" onclick="this.closest('.cp-row').remove()" style="border:none; background:none; cursor:pointer; color:#d9534f;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
    </div>
</template>
<script>
function addCheckpoint() {
    const tpl = document.getElementById('cp-template');
    const container = document.getElementById('cp-container');
    const clone = tpl.content.cloneNode(true);
    container.appendChild(clone);
}
</script>
<?php endif; ?>