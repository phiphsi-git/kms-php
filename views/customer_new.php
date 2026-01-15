<?php use App\Csrf; ?>
<section class="dash">
  <header class="dash-header">
    <div><h2>Neuer Kunde</h2><p class="muted">Stammdaten, Zuständigkeiten & Kontakte</p></div>
    <div class="actions"><a class="btn" href="?route=customers">Zurück</a></div>
  </header>

  <?php if (!empty($errors)): ?>
    <div class="alert error"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
  <?php endif; ?>

  <form method="post" action="?route=customer_create" class="form">
    <?= Csrf::field() ?>

    <h3>Stammdaten</h3>
    <label>Kundenname
      <input type="text" name="name" required value="<?= htmlspecialchars($old['name'] ?? '') ?>">
    </label>
    <div class="grid">
      <label>Adresse / Straße
        <input type="text" name="street" value="<?= htmlspecialchars($old['street'] ?? '') ?>">
      </label>
      <label>PLZ
        <input type="text" name="zip" value="<?= htmlspecialchars($old['zip'] ?? '') ?>">
      </label>
      <label>Ort
        <input type="text" name="city" value="<?= htmlspecialchars($old['city'] ?? '') ?>">
      </label>
    </div>
    <div class="grid">
      <label>Webseite
        <input type="url" name="website" placeholder="https://..." value="<?= htmlspecialchars($old['website'] ?? '') ?>">
      </label>
      <label>Logo-URL
        <input type="url" name="logo_url" placeholder="https://..." value="<?= htmlspecialchars($old['logo_url'] ?? '') ?>">
      </label>
    </div>

<h3>Wartungsintervall</h3>
<div class="grid">
  <label>Intervall
    <select name="maintenance_type" id="maintenance_type" required>
      <option value="">– auswählen –</option>
	  <option value="none">keine</option>
	  <option value="paused">pausiert</option>
      <option value="daily">täglich</option>
      <option value="weekly">wöchentlich</option>
      <option value="biweekly">alle 2 Wochen</option>
      <option value="monthly">monatlich (z. B. 1./letzter Do)</option>
      <option value="yearly">jährlich (Datum)</option>
    </select>
  </label>

  <label id="fld_pause_reason" style="display:none">Grund (bei „pausiert“)
  <input type="text" name="maintenance_pause_reason" maxlength="500" placeholder="Warum pausiert?">
  </label>
	  
  <label id="fld_time" style="display:none">Uhrzeit
    <input type="time" name="maintenance_time" value="09:00">
  </label>

  <label id="fld_weekday" style="display:none">Wochentag
    <select name="maintenance_weekday">
      <option value="1">Montag</option>
      <option value="2">Dienstag</option>
      <option value="3">Mittwoch</option>
      <option value="4">Donnerstag</option>
      <option value="5">Freitag</option>
      <option value="6">Samstag</option>
      <option value="7">Sonntag</option>
    </select>
  </label>

  <label id="fld_week_of_month" style="display:none">Woche im Monat
    <select name="maintenance_week_of_month">
      <option value="1">1. (erste)</option>
      <option value="2">2.</option>
      <option value="3">3.</option>
      <option value="4">4.</option>
      <option value="-1">letzte</option>
    </select>
  </label>

  <label id="fld_year_month" style="display:none">Monat (jährlich)
    <select name="maintenance_year_month">
      <?php for($m=1;$m<=12;$m++): ?>
        <option value="<?=$m?>"><?=$m?></option>
      <?php endfor; ?>
    </select>
  </label>

  <label id="fld_year_day" style="display:none">Tag (1–31, jährlich)
    <input type="number" min="1" max="31" name="maintenance_year_day" value="1">
  </label>
</div>

<script>
(function(){
  const type = document.getElementById('maintenance_type');
  const show = (id, on) => (document.getElementById(id).style.display = on ? '' : 'none');

  function update(){
    const t = type.value;
    const timeTypes = ['daily','weekly','biweekly','monthly'];
    const weekTypes = ['weekly','biweekly','monthly'];

    show('fld_time', timeTypes.includes(t));
    show('fld_weekday', weekTypes.includes(t));
    show('fld_week_of_month', t === 'monthly');
    show('fld_year_month', t === 'yearly');
    show('fld_year_day', t === 'yearly');
    show('fld_pause_reason', t === 'paused');
  }
  type.addEventListener('change', update);
  update();
})();
</script>



    <div class="grid">
      <label>Verantwortlicher Techniker
        <select name="responsible_technician_id">
          <option value="">– keiner –</option>
          <?php foreach ($technicians as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= (!empty($old['tech_id']) && (int)$old['tech_id']===(int)$t['id'])?'selected':'' ?>>
              <?= htmlspecialchars($t['email']) ?> (<?= htmlspecialchars($t['role']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Zugewiesener Mitarbeiter
        <select name="owner_user_id">
          <option value="">– keiner –</option>
          <?php foreach ($employees as $e): ?>
            <option value="<?= (int)$e['id'] ?>" <?= (!empty($old['owner_id']) && (int)$old['owner_id']===(int)$e['id'])?'selected':'' ?>>
              <?= htmlspecialchars($e['email']) ?> (<?= htmlspecialchars($e['role']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <h3>Kontaktpersonen</h3>
    <div id="contacts">
      <?php
        $rows = isset($oldContacts) && is_array($oldContacts) ? $oldContacts : [['general_inquiries'=>1]];
        foreach ($rows as $i => $c):
      ?>
      <fieldset class="card" data-index="<?= $i ?>">
        <div class="grid">
          <label>Name <input type="text" name="contacts[<?= $i ?>][name]" value="<?= htmlspecialchars($c['name'] ?? '') ?>"></label>
          <label>Telefon <input type="text" name="contacts[<?= $i ?>][phone]" value="<?= htmlspecialchars($c['phone'] ?? '') ?>"></label>
          <label>E-Mail <input type="email" name="contacts[<?= $i ?>][email]" value="<?= htmlspecialchars($c['email'] ?? '') ?>"></label>
        </div>
        <div class="grid">
          <label><input type="checkbox" name="contacts[<?= $i ?>][tech_questions]"        <?= !empty($c['tech_questions'])?'checked':'' ?>> technische Fragen</label>
          <label><input type="checkbox" name="contacts[<?= $i ?>][admin_questions]"       <?= !empty($c['admin_questions'])?'checked':'' ?>> administrative Fragen</label>
          <label><input type="checkbox" name="contacts[<?= $i ?>][budget_approvals]"      <?= !empty($c['budget_approvals'])?'checked':'' ?>> Budgetfreigaben</label>
          <label><input type="checkbox" name="contacts[<?= $i ?>][credential_changes]"    <?= !empty($c['credential_changes'])?'checked':'' ?>> Logindaten ändern</label>
          <label><input type="checkbox" name="contacts[<?= $i ?>][ticket_creation]"       <?= !empty($c['ticket_creation'])?'checked':'' ?>> Tickets aufgeben</label>
          <label><input type="checkbox" name="contacts[<?= $i ?>][general_inquiries]"     <?= (!isset($c['general_inquiries'])||$c['general_inquiries'])?'checked':'' ?>> allgemeine Anfragen</label>
        </div>
        <button type="button" class="btn" onclick="this.closest('fieldset').remove()">Entfernen</button>
      </fieldset>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn" id="addContactBtn">+ Kontakt hinzufügen</button>

    <div style="margin-top:12px">
      <button class="btn primary" type="submit">Speichern</button>
    </div>
  </form>
</section>

<script>
(function(){
  const container = document.getElementById('contacts');
  const btn = document.getElementById('addContactBtn');
  btn.addEventListener('click', () => {
    const i = container.querySelectorAll('fieldset').length;
    const tpl = `
      <fieldset class="card" data-index="${i}">
        <div class="grid">
          <label>Name <input type="text" name="contacts[${i}][name]"></label>
          <label>Telefon <input type="text" name="contacts[${i}][phone]"></label>
          <label>E-Mail <input type="email" name="contacts[${i}][email]"></label>
        </div>
        <div class="grid">
          <label><input type="checkbox" name="contacts[${i}][tech_questions]"> technische Fragen</label>
          <label><input type="checkbox" name="contacts[${i}][admin_questions]"> administrative Fragen</label>
          <label><input type="checkbox" name="contacts[${i}][budget_approvals]"> Budgetfreigaben</label>
          <label><input type="checkbox" name="contacts[${i}][credential_changes]"> Logindaten ändern</label>
          <label><input type="checkbox" name="contacts[${i}][ticket_creation]"> Tickets aufgeben</label>
          <label><input type="checkbox" name="contacts[${i}][general_inquiries]" checked> allgemeine Anfragen</label>
        </div>
        <button type="button" class="btn" onclick="this.closest('fieldset').remove()">Entfernen</button>
      </fieldset>
    `;
    container.insertAdjacentHTML('beforeend', tpl);
  });
})();
</script>
