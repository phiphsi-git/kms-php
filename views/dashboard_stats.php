<section class="grid-2">
  <div class="card">
    <h3>Aufgaben nach Status</h3>
    <?php foreach ($stats['byStatus'] as $st=>$cnt): ?>
      <div class="row"><strong><?= htmlspecialchars($st) ?></strong><span><?= (int)$cnt ?></span></div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <h3>Top Kunden (offene Aufgaben)</h3>
    <ul class="simple-list">
      <?php foreach ($stats['topCustomers'] as $r): ?>
        <li><?= htmlspecialchars($r['name']) ?> · <strong><?= (int)$r['cnt'] ?></strong></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="card" style="grid-column:1 / -1">
    <h3>Nächste 14 Tage</h3>
    <ul class="simple-list">
      <?php foreach ($stats['nextDays'] as $r): ?>
        <li><?= date('d.m.Y', strtotime($r['d'])) ?> · <strong><?= (int)$r['cnt'] ?></strong> Aufgaben</li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<style>.row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border)}</style>
