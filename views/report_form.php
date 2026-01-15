<?php
use App\Csrf;
$cid = (int)$customer_id;
$nowCH = new DateTimeImmutable('now', new DateTimeZone('Europe/Zurich'));
$default = $nowCH->format('Y-m-d\TH:i'); // für datetime-local
?>
<section class="card" style="max-width:560px;margin:0 auto">
  <h3>PDF-Report erzeugen</h3>
  <form method="post" action="?route=report_generate">
    <?= Csrf::field() ?>
    <input type="hidden" name="customer_id" value="<?= $cid ?>">

    <label>Zeitraum
      <div class="muted" style="margin-bottom:6px">
        Es werden alle Aufgaben-Änderungen <strong>seit dem letzten Report</strong> bis zum gewählten Zeitpunkt berücksichtigt.
      </div>
      <input type="datetime-local" name="to" value="<?= htmlspecialchars($default) ?>" required>
    </label>

    <div class="actions" style="margin-top:12px">
      <a class="btn" href="?route=customer_view&id=<?= $cid ?>">Abbrechen</a>
      <button class="btn primary" type="submit">Report erzeugen</button>
    </div>
  </form>
</section>
