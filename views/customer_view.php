<?php
use App\Csrf; use App\Policy;
$customerId = (int)$c['id'];
$systems = \App\SystemRepo::listByCustomer($customerId);
$tasks   = \App\TaskRepo::listByCustomer($customerId);
$files   = \App\FileRepo::listByCustomer($customerId);
$reports = class_exists('\App\ReportRepo') ? \App\ReportRepo::listByCustomer($customerId) : [];
$logs    = class_exists('\App\ChangeLogRepo') ? \App\ChangeLogRepo::list($customerId) : [];
$licCount = \App\DB::pdo()->query("SELECT COUNT(*) FROM customer_licenses WHERE customer_id=$customerId")->fetchColumn();
$netCount = \App\DB::pdo()->query("SELECT COUNT(*) FROM customer_networks WHERE customer_id=$customerId")->fetchColumn();
$cnt = ['offen'=>0,'ausstehend'=>0,'erledigt'=>0]; foreach ($tasks as $t) { $st = $t['status'] ?? ''; if (isset($cnt[$st])) $cnt[$st]++; }
?>

<style>
.dash { max-width: 1200px; margin: 0 auto; padding: 20px; font-family: -apple-system, sans-serif; color:#333; }
/* --- NEUER STIL FÜR ERROR BOX --- */
.alert-box { padding: 15px; margin-bottom: 20px; border-radius: 6px; font-weight: 500; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
/* -------------------------------- */
.dash-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; flex-wrap: wrap; gap: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
.dash-title h2 { margin: 0 0 5px 0; font-size: 2rem; color: #222; }
.muted { color: #777; font-size: 0.95rem; }
.actions { display: flex; flex-wrap: wrap; gap: 8px; }
.btn-icon { display: inline-flex; align-items: center; justify-content: center; gap: 6px; background: #fff; border: 1px solid #ccc; border-radius: 6px; color: #555; padding: 8px 12px; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.2s ease; cursor: pointer; line-height: 1; }
.btn-icon:hover { background: #f5f5f5; border-color: #bbb; color: #333; transform: translateY(-1px); }
.btn-keeper:hover { border-color: #FFC600; color: #333; background: #fffdf5; }
.btn-icon svg { width: 18px; height: 18px; stroke-width: 2; }
.btn-primary { background: #0056b3; border-color: #004a99; color: #fff; } .btn-danger { color: #d9534f; border-color: #ffcccc; background: #fff5f5; }
.btn-sq { padding: 8px; width: 36px; height: 36px; }
.cards-row { display: flex; gap: 20px; margin-bottom: 30px; }
.card-stat { flex: 1; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.card-stat h3 { margin-top: 0; font-size: 1rem; text-transform: uppercase; color: #666; }
.stat-value { font-size: 2rem; font-weight: bold; color: #333; margin: 10px 0 0 0; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px; }
.content-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 0; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; flex-direction:column; }
.card-head { background: #fcfcfc; padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
.card-head h3 { margin: 0; font-size: 1.1rem; color: #333; }
.scroll-list { max-height: 300px; overflow-y: auto; }
.list-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border-bottom: 1px solid #f0f0f0; }
.list-item:hover { background: #fafafa; }
.item-meta { font-size: 0.85rem; color: #888; margin-top: 4px; }
.log-wrapper { max-height: 400px; overflow-y: auto; }
.log-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.log-table th { text-align: left; padding: 10px 15px; background: #f8f9fa; border-bottom: 1px solid #ddd; position: sticky; top: 0; color: #555; }
.log-table td { padding: 8px 15px; border-bottom: 1px solid #eee; color: #444; }
.badge-manual { background: #e3f2fd; color: #0d47a1; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; border: 1px solid #bbdefb; }
@media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } .cards-row { flex-direction: column; } }
</style>

<section class="dash">

  <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'report_ok'): ?>
      <div class="alert-box alert-success">✅ Report erfolgreich erstellt.</div>
  <?php endif; ?>
  <?php if (!empty($_GET['err'])): ?>
      <div class="alert-box alert-error">⚠️ Fehler: <?= htmlspecialchars($_GET['err']) ?></div>
  <?php endif; ?>
  <header class="dash-header">
    <div class="dash-title">
      <div style="display:flex; align-items:center; gap:12px;">
        <h2><?= htmlspecialchars($c['name']) ?></h2>
        <?php $raType = $c['remote_access_type'] ?? ''; if ($raType): 
            $iconFile = null; $map = ['anydesk'=>'Any_Desk.png','citrix'=>'Citrix.jpg','forticlient'=>'Forticlient.png','fritzbox'=>'FritzBox.png','microsoft'=>'Microsoft.png','openvpn'=>'OpenVPN.png','rdp'=>'RDP.png','sophos'=>'Sophos.jpg','ssh'=>'SSH.png','swisscom_pex'=>'Swisscom_PEX.jpg','swisscom_ras'=>'Swisscom_RAS.png','teamviewer'=>'Teamviewer.jpg','unifi_ui'=>'UniFi_UI.jpg','unifi_wifiman'=>'UniFi_Wifiman.jpg','wireguard'=>'Wireguard.png']; if (isset($map[$raType])) $iconFile = $map[$raType];
            if ($iconFile) { echo '<div class="btn-icon btn-sq" title="'.ucfirst($raType).'" style="cursor:default; border:none; padding:0;"><img src="assets/img/'.$iconFile.'" alt="'.$raType.'" style="width:36px; height:36px; border-radius:4px; object-fit:contain; border:1px solid #ddd;"></div>'; } else { echo '<div class="btn-icon btn-sq" title="'.ucfirst($raType).'" style="color:#555; cursor:default;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg></div>'; }
        endif; ?>
        <?php if (!empty($c['password_manager_url'])): ?>
            <a href="<?= htmlspecialchars($c['password_manager_url']) ?>" target="_blank" title="Keeper Security öffnen" style="display:inline-block;"><img src="assets/img/keeper.png" alt="Keeper" style="width:36px; height:36px; border-radius:4px; object-fit:contain; border:1px solid #FFC600;"></a>
        <?php endif; ?>
      </div>
      <div class="muted"><?= htmlspecialchars(trim(($c['street']??'').', '.($c['zip']??'').' '.($c['city']??''))) ?></div>
    </div>
    <div class="actions">
      <a class="btn-icon" href="?route=customers"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg> Zurück</a>
      <a class="btn-icon" href="?route=customer_wiki&id=<?= $customerId ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Wiki</a>
      <?php if (Policy::can('systems.create')): ?><a class="btn-icon" href="?route=system_new&customer_id=<?= $customerId ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> System</a><?php endif; ?>
      <?php if (Policy::can('tasks.create')): ?><a class="btn-icon" href="?route=task_new&customer_id=<?= $customerId ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg> Aufgabe</a><?php endif; ?>
      <?php if (Policy::can('files.upload')): ?><a class="btn-icon" href="?route=file_new&customer_id=<?= $customerId ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg> Datei</a><?php endif; ?>
      <?php if (class_exists('\App\ReportRepo') && \App\Policy::can('customers.view')): ?><a class="btn-icon" href="?route=report_form&customer_id=<?= $customerId ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Report</a><?php endif; ?>
      <?php if (Policy::can('customers.update')): ?><a class="btn-icon btn-sq" href="?route=customer_edit&id=<?= $customerId ?>" title="Kunde bearbeiten"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a><?php endif; ?>
    </div>
  </header>

  <div class="cards-row">
    <div class="card-stat"><h3>Systeme</h3><div class="stat-value"><?= (int)($c['systems_count'] ?? count($systems)) ?></div></div>
    <div class="card-stat"><h3>Offene Aufgaben</h3><div class="stat-value" style="color:<?= $cnt['offen']>0 ? '#d9534f':'#28a745' ?>"><?= (int)$cnt['offen'] ?></div></div>
    <div class="card-stat"><h3>Netzwerk / Lizenzen</h3><div class="stat-value" style="color:#555; font-size:1.5rem; display:flex; gap:15px;">
        <div><small style="font-size:0.4em; display:block; text-transform:uppercase;">Netzwerke</small><?= $netCount ?></div>
        <div style="border-left:1px solid #ddd; padding-left:15px;"><small style="font-size:0.4em; display:block; text-transform:uppercase;">Lizenzen</small><?= $licCount ?></div>
    </div></div>
  </div>

  <div style="margin-bottom:20px; display:flex; gap:15px;">
      <a href="?route=customer_networks&id=<?= $customerId ?>" class="card" style="flex:1; text-decoration:none; padding:15px; display:flex; align-items:center; gap:15px; border:1px solid #e0e0e0; transition:all 0.2s;">
          <div style="background:#e3f2fd; padding:10px; border-radius:50%; color:#0d47a1;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
          </div>
          <div><strong style="color:#333; display:block;">Netzwerk-Planer</strong><span style="color:#777; font-size:0.9em;">IP-Schema, VLANs & Standorte</span></div>
      </a>
      <a href="?route=customer_licenses&id=<?= $customerId ?>" class="card" style="flex:1; text-decoration:none; padding:15px; display:flex; align-items:center; gap:15px; border:1px solid #e0e0e0; transition:all 0.2s;">
          <div style="background:#e8f5e9; padding:10px; border-radius:50%; color:#1b5e20;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><path d="M20 6L9 17l-5-5"></path></svg>
          </div>
          <div><strong style="color:#333; display:block;">Lizenz-Inventar</strong><span style="color:#777; font-size:0.9em;">Software, Keys & Ablaufdaten</span></div>
      </a>
  </div>

  <section class="grid-2">
    <div class="content-card">
      <div class="card-head"><h3>Systeme</h3></div>
      <div class="scroll-list"><?php if (empty($systems)): ?><div style="padding:20px; color:#888;">Keine Systeme.</div><?php else: ?><?php foreach ($systems as $s): ?><div class="list-item"><div><strong><?= htmlspecialchars($s['name']) ?></strong><div class="item-meta"><?= htmlspecialchars($s['type']) ?> <?= !empty($s['role']) ? '· '.htmlspecialchars($s['role']) : '' ?></div></div><?php if (Policy::can('systems.update')): ?><div class="actions"><a class="btn-icon btn-sq" href="?route=system_edit&id=<?= $s['id'] ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a><form method="post" action="?route=system_delete&id=<?= $s['id'] ?>" onsubmit="return confirm('Löschen?');" style="display:inline"><?= Csrf::field() ?><button class="btn-icon btn-sq btn-danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button></form></div><?php endif; ?></div><?php endforeach; ?><?php endif; ?></div>
    </div>
    <div class="content-card">
      <div class="card-head"><h3>Aufgaben</h3></div>
      <div class="scroll-list"><?php if (empty($tasks)): ?><div style="padding:20px; color:#888;">Keine Aufgaben.</div><?php else: ?><?php foreach ($tasks as $t): ?><div class="list-item"><div><strong><?= htmlspecialchars($t['title']) ?></strong><div class="item-meta">Status: <?= htmlspecialchars($t['status']) ?> <?= !empty($t['due_date']) ? '· Fällig: '.date('d.m.y', strtotime($t['due_date'])) : '' ?></div></div><?php if (Policy::can('tasks.update')): ?><div class="actions"><a class="btn-icon btn-sq" href="?route=task_edit&id=<?= $t['id'] ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a><form method="post" action="?route=task_delete&id=<?= $t['id'] ?>" onsubmit="return confirm('Löschen?');" style="display:inline"><?= Csrf::field() ?><button class="btn-icon btn-sq btn-danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button></form></div><?php endif; ?></div><?php endforeach; ?><?php endif; ?></div>
    </div>
  </section>

  <section class="grid-2">
    <div class="content-card"><div class="card-head"><h3>Dateien</h3><small class="muted"><?= count($files) ?></small></div><div class="scroll-list"><?php if (empty($files)): ?><div style="padding:20px; color:#888; text-align:center;">Keine Dateien.</div><?php else: ?><?php foreach ($files as $f): ?><div class="list-item"><div style="overflow:hidden;"><div style="font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($f['original_name']) ?>"><?= htmlspecialchars($f['original_name']) ?></div><div class="item-meta"><?= date('d.m.y', strtotime($f['created_at'])) ?></div></div><div class="actions"><a class="btn-icon btn-sq" href="?route=file_preview&id=<?= $f['id'] ?>" target="_blank"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></a><a class="btn-icon btn-sq" href="?route=file_download&id=<?= $f['id'] ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></a><?php if (Policy::can('files.delete')): ?><form method="post" action="?route=file_delete&id=<?= $f['id'] ?>" onsubmit="return confirm('Löschen?');" style="display:inline"><?= Csrf::field() ?><button class="btn-icon btn-sq btn-danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button></form><?php endif; ?></div></div><?php endforeach; ?><?php endif; ?></div></div>
    <div class="content-card"><div class="card-head"><h3>Reports</h3><small class="muted"><?= count($reports) ?></small></div><div class="scroll-list"><?php if (empty($reports)): ?><div style="padding:20px; color:#888; text-align:center;">Keine Reports.</div><?php else: ?><?php foreach ($reports as $r): ?><div class="list-item"><div><div style="font-weight:600;"><?= htmlspecialchars($r['title']) ?></div><div class="item-meta"><?= date('d.m.Y H:i', strtotime($r['created_at'])) ?></div></div><div class="actions"><a class="btn-icon btn-sq" href="?route=report_download&id=<?= $r['id'] ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></a><?php if (Policy::can('customers.update')): ?><form method="post" action="?route=report_delete&id=<?= $r['id'] ?>" onsubmit="return confirm('Löschen?');" style="display:inline"><?= Csrf::field() ?><button class="btn-icon btn-sq btn-danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button></form><?php endif; ?></div></div><?php endforeach; ?><?php endif; ?></div></div>
  </section>

  <div class="content-card" style="margin-bottom:30px;">
    <div class="card-head"><h3>Aktivitätenprotokoll</h3><div style="display:flex; gap:8px;"><input type="text" id="logSearch" placeholder="Suchen..." onkeyup="filterLog()" style="padding:6px 10px; border:1px solid #ccc; border-radius:4px; font-size:0.9rem;"><a href="?route=changelog_export&customer_id=<?= $customerId ?>" class="btn-icon btn-sq" title="Export CSV"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></a><?php if (Policy::hasRole('Admin')): ?><form method="post" action="?route=changelog_clear&customer_id=<?= $customerId ?>" onsubmit="return confirm('Protokoll wirklich leeren?');" style="display:inline"><?= Csrf::field() ?><button class="btn-icon btn-sq btn-danger" title="Protokoll leeren"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button></form><?php endif; ?></div></div>
    <form method="post" action="?route=changelog_add" style="padding:15px; border-bottom:1px solid #eee; background:#fdfdfd; display:flex; gap:10px;"><?= Csrf::field() ?><input type="hidden" name="customer_id" value="<?= $customerId ?>"><input type="text" name="note" placeholder="Eintrag hinzufügen..." required style="flex:1; padding:8px; border:1px solid #ccc; border-radius:4px;"><button type="submit" class="btn-icon btn-primary" style="padding-left:15px; padding-right:15px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> Speichern</button></form>
    <div class="log-wrapper"><table class="log-table" id="logTable"><thead><tr><th width="15%">Zeit</th><th width="15%">Wer</th><th width="10%">Typ</th><th width="10%">Aktion</th><th width="50%">Notiz / Link</th></tr></thead><tbody><?php foreach ($logs as $l): ?><tr><td style="color:#777; font-size:0.85rem;"><?= date('d.m.y H:i', strtotime($l['created_at'])) ?></td><td><?= htmlspecialchars($l['user_email'] ?? 'System') ?></td><td><?= htmlspecialchars($l['entity_type']) ?></td><td><?= htmlspecialchars($l['action_type']) ?></td><td><?php if($l['entity_type']==='manual'): ?><span class="badge-manual"><?= htmlspecialchars($l['note']) ?></span><?php else: ?><?= htmlspecialchars($l['note']) ?><?php endif; ?><?php if(!empty($l['ref_link'])): ?><a href="<?= htmlspecialchars($l['ref_link']) ?>" target="_blank" style="margin-left:5px; color:#0056b3;">🔗 Link</a><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
  </div>

  <?php if (\App\Policy::can('customers.delete')): ?>
    <div style="text-align:right; margin-top:30px;"><form method="post" action="?route=customer_delete&id=<?= $customerId ?>" onsubmit="return confirm('Kunde wirklich komplett löschen? Dies kann nicht rückgängig gemacht werden!');" style="display:inline"><?= Csrf::field() ?><button class="btn-icon btn-danger" style="padding:10px 16px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Kunde komplett löschen</button></form></div>
  <?php endif; ?>
</section>
<script>function filterLog() { const input = document.getElementById('logSearch'); const filter = input.value.toLowerCase(); const rows = document.querySelectorAll('#logTable tbody tr'); rows.forEach(row => { const text = row.textContent.toLowerCase(); row.style.display = text.includes(filter) ? '' : 'none'; }); }</script>
}