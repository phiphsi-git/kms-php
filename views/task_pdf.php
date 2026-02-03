<?php
// simple printable view
$sysNames = [];
$sysMap = [];
foreach (($systems ?? []) as $s) $sysMap[(int)$s['id']] = $s['name'] ?? '';
foreach (($selectedSystemIds ?? []) as $sid) $sysNames[] = $sysMap[(int)$sid] ?? ('#'.$sid);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Aufgabe <?= (int)$t['id'] ?></title>
  <style>
    body{font-family: Arial, sans-serif; font-size: 12px;}
    h1{font-size: 18px; margin:0 0 8px}
    table{width:100%; border-collapse: collapse; margin-top:10px}
    th,td{border:1px solid #ccc; padding:6px; vertical-align: top}
    th{background:#f3f3f3}
    .muted{color:#666}
  </style>
</head>
<body>
  <h1>Aufgabe #<?= (int)$t['id'] ?> – <?= htmlspecialchars($t['title'] ?? '') ?></h1>
  <div class="muted">
    Status: <?= htmlspecialchars($t['status'] ?? '') ?> ·
    Fällig: <?= htmlspecialchars($t['due_date'] ?? '—') ?> ·
    Systeme: <?= htmlspecialchars($sysNames ? implode(', ', $sysNames) : '—') ?>
  </div>

  <h3>Kontrollpunkte</h3>
  <table>
    <thead>
      <tr>
        <th style="width:60%">Kontrollpunkt</th>
        <th style="width:10%">OK</th>
        <th style="width:30%">Kommentar</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($checkpoints)): ?>
        <tr><td colspan="3" class="muted">Keine Kontrollpunkte.</td></tr>
      <?php else: foreach ($checkpoints as $cp): ?>
        <tr>
          <td><?= htmlspecialchars($cp['label'] ?? '') ?></td>
          <td><?= !empty($cp['is_done']) ? '✓' : '—' ?></td>
          <td><?= htmlspecialchars($cp['comment'] ?? '') ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</body>
</html>
