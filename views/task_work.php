<?php use App\Csrf; ?>
<section class="dash">
  <header class="dash-header">
    <div><h2>Aufgabe #<?= (int)$t['id'] ?> – <?= htmlspecialchars($t['title'] ?? '') ?></h2></div>
    <div class="actions"><a class="btn" href="?route=customer_view&id=<?= (int)$t['customer_id'] ?>">Zurück</a></div>
  </header>

  <?php if (!empty($errors)): ?>
    <div class="alert error"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
  <?php endif; ?>

  <form method="post" action="?route=task_work_save&id=<?= (int)$t['id'] ?>" class="form">
    <?= Csrf::field() ?>

    <div class="card" style="margin-top:12px">
      <div class="card-head-between">
        <h3>Kontrollpunkte</h3>
      </div>

      <div id="cpRows" class="cp-grid">
        <?php if (empty($checkpoints)): ?>
          <p class="muted">Keine Kontrollpunkte.</p>
        <?php else: foreach ($checkpoints as $i=>$cp): ?>
          <div class="cp-row">
            <input type="hidden" name="cp_id[]" value="<?= (int)$cp['id'] ?>">
            <input type="hidden" name="cp_order[]" value="<?= (int)($cp['sort_order'] ?? $i) ?>">
            <input type="hidden" name="cp_label[]" value="<?= htmlspecialchars($cp['label'] ?? '') ?>">
            <input type="hidden" name="cp_reqcmt[<?= $i ?>]" value="<?= !empty($cp['require_comment_on_fail']) ? 1 : 0 ?>">

            <label class="cp-ok" title="OK">
              <input type="checkbox" name="cp_done[<?= $i ?>]" value="1" <?= !empty($cp['is_done']) ? 'checked' : '' ?>>
            </label>

            <div class="cp-text" style="padding:6px 8px;">
              <?= htmlspecialchars($cp['label'] ?? '') ?>
            </div>

            <input class="cp-comment" type="text" name="cp_comment[]" value="<?= htmlspecialchars($cp['comment'] ?? '') ?>"
                   placeholder="Kommentar (falls nicht ok)">
          </div>
        <?php endforeach; endif; ?>
      </div>

      <small class="muted">Tipp: Kommentar nur nötig, wenn „nicht ok“ und Bemerkungspflicht aktiv.</small>
    </div>

    <div class="flex gap">
      <button class="btn primary" type="submit">Speichern</button>
    </div>
  </form>
</section>
