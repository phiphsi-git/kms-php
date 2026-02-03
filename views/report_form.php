<?php
use App\Csrf;
$cid = (int)$customer_id;
$c = \App\CustomerRepo::findWithDetails($cid);
$lastReportDate = \App\ReportRepo::getLastReportDate($cid);
$defaultFrom = $lastReportDate ? date('Y-m-d', strtotime($lastReportDate . ' +1 day')) : date('Y-m-d', strtotime('-1 month'));
?>
<section class="dash" style="max-width:800px; margin:0 auto; padding:20px;">
    <h2>Bericht generieren: <?= htmlspecialchars($c['name']) ?></h2>
    <div class="content-card">
        <form method="post" action="?route=report_generate" enctype="multipart/form-data" style="padding:20px;">
            <?= Csrf::field() ?>
            <input type="hidden" name="customer_id" value="<?= $cid ?>">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                <div><label>Von:</label><input type="date" name="from_date" value="<?= $defaultFrom ?>" required style="width:100%;"></div>
                <div><label>Bis:</label><input type="date" name="to_date" value="<?= date('Y-m-d') ?>" required style="width:100%;"></div>
            </div>
            <div style="margin-bottom:20px;">
                <label>Bemerkungen:</label>
                <textarea name="comment" rows="4" style="width:100%;" placeholder="Einleitungstext für den Kunden..."></textarea>
            </div>
            <div style="margin-bottom:20px; padding:15px; border:1px dashed #ccc; background:#fafafa;">
                <label>Anlagen hinzufügen:</label>
                <input type="file" name="attachments[]" multiple>
            </div>
            <div style="text-align:right;">
                <button type="submit" class="btn-primary" style="padding:10px 25px;">PDF Report erstellen</button>
            </div>
        </form>
    </div>
</section>