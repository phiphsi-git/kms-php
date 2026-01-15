<?php
declare(strict_types=1);

use App\Auth;
use App\Config;
use App\Csrf;
use App\DB;
use App\Middleware;
use App\UserRepo;
use App\PasswordPolicy;

ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../error.log');  // Log nach /kms-php/error.log
error_reporting(E_ALL);

require_once __DIR__.'/../src/Config.php';
require_once __DIR__.'/../src/DB.php';
require_once __DIR__.'/../src/PasswordPolicy.php';
require_once __DIR__.'/../src/Csrf.php';
require_once __DIR__.'/../src/UserRepo.php';
require_once __DIR__.'/../src/Auth.php';
require_once __DIR__.'/../src/Middleware.php';
require_once __DIR__.'/../src/Policy.php';
require_once __DIR__.'/../src/CustomerRepo.php';
require_once __DIR__.'/../src/TaskRepo.php';
require_once __DIR__.'/../src/SystemRepo.php';
require_once __DIR__.'/../src/ReportRepo.php';
require_once __DIR__.'/../src/FileRepo.php';
require_once __DIR__.'/../src/TaskCheckpointRepo.php';
require_once __DIR__.'/../src/TaskStatusLogRepo.php';




Auth::start();

// Flash helper
function flash_take(): array {
  $f = $_SESSION['flash'] ?? [];
  unset($_SESSION['flash']);
  return $f;
}
function flash_set(string $key, string $msg): void {
  $_SESSION['flash'][$key] = $msg;
}

// Simple view renderer
function render(string $view, array $data = []): void {
  extract($data, EXTR_SKIP);
  ob_start();
  require __DIR__.'/../views/'.$view.'.php';
  $content = ob_get_clean();
  require __DIR__.'/../views/layout.php';
}

$route = $_GET['route'] ?? (Auth::check() ? 'dashboard' : 'login');

switch ($route) {
  case 'login':
    if (Auth::check()) { header('Location: ?route=dashboard'); exit; }
    $flash = flash_take();
    render('login', compact('flash'));
    break;

  case 'login_post':
    if (!Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (Auth::attempt($email, $password)) {
      header('Location: ?route=dashboard'); exit;
    }
    flash_set('error', 'Ungültige Zugangsdaten.');
    header('Location: ?route=login'); exit;

  case 'logout':
    Auth::logout();
    flash_set('success', 'Erfolgreich abgemeldet.');
    header('Location: ?route=login'); exit;

	case 'dashboard':
	  \App\Middleware::requireAuth();
	  $tasks = \App\TaskRepo::listOpenDue();
	  $groups = \App\TaskRepo::groupByDueBuckets($tasks);
	  render('dashboard', compact('groups'));
	  break;

// Nutzerliste
case 'users':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('users.view')) { http_response_code(403); exit('Forbidden'); }
  $q    = trim($_GET['q'] ?? '');
  $sort = $_GET['sort'] ?? 'email_asc'; // email_asc|email_desc|role_asc|role_desc|status_desc
  $users = \App\UserRepo::listAll($q, $sort);
  render('users_list', compact('users','q','sort'));
  break;

// Nutzer erstellen
  case 'users_create':
    Middleware::requireRole(['Admin','Projektleiter']);
    if (!Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Mitarbeiter';
    $res = UserRepo::create($email, $password, $role);
    if ($res['ok']) {
      render('users', ['errors'=>[], 'success'=>'Benutzer angelegt.']);
    } else {
      render('users', ['errors'=>$res['errors'], 'success'=>null]);
    }
    break;
	
// Nutzer bearbeiten (Form)
case 'user_edit':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('users.manage')) { http_response_code(403); exit('Forbidden'); }
  $id = (int)($_GET['id'] ?? 0);
  $u  = \App\UserRepo::findById($id);
  if (!$u) { http_response_code(404); exit('Not Found'); }
  render('user_edit', ['u'=>$u, 'errors'=>[]]);
  break;

// Nutzer speichern (POST)
case 'user_update':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('users.manage')) { http_response_code(403); exit('Forbidden'); }
  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
  $id    = (int)($_GET['id'] ?? 0);
  $email = trim($_POST['email'] ?? '');
  $role  = trim($_POST['role'] ?? '');
  $active= isset($_POST['is_active']) ? 1 : 0;

  $res = \App\UserRepo::updateUser($id, $email, $role, $active);
  if ($res['ok']) { header('Location: ?route=users'); exit; }
  $u = \App\UserRepo::findById($id);
  render('user_edit', ['u'=>$u, 'errors'=>$res['errors'] ?? ['Unbekannter Fehler']]);
  break;

// Passwort-Reset (Form)
case 'user_password':
  \App\Middleware::requireAuth();
  $id = (int)($_GET['id'] ?? 0);
  $target = \App\UserRepo::findById($id);
  if (!$target) { http_response_code(404); exit('Not Found'); }
  $actor = \App\Auth::user();
  if (!\App\Policy::canResetPasswordOf($actor, $target)) { http_response_code(403); exit('Forbidden'); }
  render('user_password', ['u'=>$target, 'errors'=>[]]);
  break;

// Passwort-Reset (POST)
case 'user_password_post':
  \App\Middleware::requireAuth();
  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
  $id = (int)($_GET['id'] ?? 0);
  $target = \App\UserRepo::findById($id);
  if (!$target) { http_response_code(404); exit('Not Found'); }
  $actor = \App\Auth::user();
  if (!\App\Policy::canResetPasswordOf($actor, $target)) { http_response_code(403); exit('Forbidden'); }

  $pw1 = $_POST['new'] ?? '';
  $pw2 = $_POST['confirm'] ?? '';
  $errs = [];
  if ($pw1 !== $pw2) $errs[] = 'Passwörter stimmen nicht überein.';
  $check = \App\PasswordPolicy::validate($pw1);
  $errs = array_merge($errs, $check);

  if ($errs) { render('user_password', ['u'=>$target,'errors'=>$errs]); break; }

  $res = \App\UserRepo::setPassword($id, $pw1);
  if ($res['ok']) { header('Location: ?route=users'); exit; }
  render('user_password', ['u'=>$target, 'errors'=>$res['errors'] ?? ['Fehler beim Setzen des Passworts']]);
  break;

case 'user_new':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('users.update')) { http_response_code(403); exit('Forbidden'); }
  $errors = [];
  $u = null; // Formular leer
  render('user_edit', compact('u','errors'));
  break;

case 'user_create':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('users.update')) { http_response_code(403); exit('Forbidden'); }
  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF'); }

  $email = trim($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $role = (string)($_POST['role'] ?? 'Mitarbeiter');

  $res = \App\UserRepo::create($email, $password, $role);
  if (!empty($_POST['locked'])) {
    // gesperrt = is_active = 0
    $st = \App\DB::pdo()->prepare("UPDATE users SET is_active=0 WHERE email=?");
    $st->execute([$email]);
  }

  if (!($res['ok'] ?? false)) {
    $errors = $res['errors'] ?? ['Fehler beim Anlegen.'];
    $u = ['email'=>$email,'role'=>$role,'locked'=>!empty($_POST['locked'])];
    render('user_edit', compact('u','errors'));
  }

  header('Location: ?route=users'); exit;

case 'customers':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('customers.view')) { http_response_code(403); exit('Forbidden'); }
  $q    = trim($_GET['q'] ?? '');
  $sort = $_GET['sort'] ?? 'name_asc'; // defaults: name_asc|name_desc|next_due_asc|next_due_desc|systems_desc
  $customers = \App\CustomerRepo::listWithStats($q, $sort);
  render('customers_list', compact('customers','q','sort'));
  break;

case 'account':
  \App\Middleware::requireAuth();
  render('account');
  break;

case 'account_password':
  \App\Middleware::requireAuth();
  $flash = flash_take();
  render('account_password', ['flash' => $flash, 'errors' => []]);
  break;

case 'account_password_post':
  \App\Middleware::requireAuth();
  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }

  $current = $_POST['current'] ?? '';
  $new     = $_POST['new'] ?? '';
  $confirm = $_POST['confirm'] ?? '';
  $errors  = [];

  // aktuelles PW prüfen
  $u = \App\Auth::user();
  $st = \App\DB::pdo()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
  $st->execute([$u['id']]);
  $hash = $st->fetchColumn();
  if (!$hash || !password_verify($current, $hash)) {
    $errors[] = 'Aktuelles Passwort ist falsch.';
  }

  // Policy / Regeln
  $pwErrors = \App\PasswordPolicy::validate($new);
  $errors = array_merge($errors, $pwErrors);
  if ($new !== $confirm) $errors[] = 'Passwörter stimmen nicht überein.';

  if ($errors) {
    render('account_password', ['errors'=>$errors, 'flash'=>[]]);
    break;
  }

  // speichern + Redirect
  $newHash = password_hash($new, PASSWORD_DEFAULT);
  $upd = \App\DB::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
  $upd->execute([$newHash, $u['id']]);

  flash_set('success', 'Passwort wurde geändert.');
  header('Location: ?route=account'); exit;


case 'customer_new':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('customers.create')) { http_response_code(403); exit('Forbidden'); }
  // Listen für Dropdowns
  $technicians = \App\UserRepo::listByRoles(['LeitenderTechniker','Techniker']);
  $employees   = \App\UserRepo::listByRoles(['Admin','Projektleiter','LeitenderTechniker','Techniker','Mitarbeiter','Lernender']);
  render('customer_new', compact('technicians','employees'));
  break;

case 'customer_create':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('customers.create')) { http_response_code(403); exit('Forbidden'); }
  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }

  $data = [
    'name'     => trim($_POST['name'] ?? ''),
    'street'   => trim($_POST['street'] ?? ''),
    'zip'      => trim($_POST['zip'] ?? ''),
    'city'     => trim($_POST['city'] ?? ''),
    'website'  => trim($_POST['website'] ?? ''),
    'logo_url' => trim($_POST['logo_url'] ?? ''),

    'maintenance_type'          => $_POST['maintenance_type'] ?? null,
    'maintenance_time'          => $_POST['maintenance_time'] ?? null,
	'maintenance_pause_reason' => trim($_POST['maintenance_pause_reason'] ?? ''),
    'maintenance_weekday'       => isset($_POST['maintenance_weekday']) ? (int)$_POST['maintenance_weekday'] : null,
    'maintenance_week_of_month' => isset($_POST['maintenance_week_of_month']) ? (int)$_POST['maintenance_week_of_month'] : null,
    'maintenance_year_month'    => isset($_POST['maintenance_year_month']) ? (int)$_POST['maintenance_year_month'] : null,
    'maintenance_year_day'      => isset($_POST['maintenance_year_day']) ? (int)$_POST['maintenance_year_day'] : null,

    'tech_id'  => (int)($_POST['responsible_technician_id'] ?? 0) ?: null,
    'owner_id' => (int)($_POST['owner_user_id'] ?? 0) ?: null,
  ];

  // Kontakte kommen als verschachteltes Array contacts[i][field]
  $contacts = $_POST['contacts'] ?? [];

  $res = \App\CustomerRepo::create($data, $contacts);
  if ($res['ok']) {
    header('Location: ?route=customers'); exit;
  } else {
    $technicians = \App\UserRepo::listByRoles(['LeitenderTechniker','Techniker']);
    $employees   = \App\UserRepo::listByRoles(['Admin','Projektleiter','LeitenderTechniker','Techniker','Mitarbeiter','Lernender']);
    render('customer_new', ['technicians'=>$technicians,'employees'=>$employees,'errors'=>$res['errors'],'old'=>$data,'oldContacts'=>$contacts]);
  }
  break;


case 'customer_edit':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('customers.update')) { http_response_code(403); exit('Forbidden'); }
  if (!isset($_GET['id'])) { http_response_code(400); exit('Fehlende ID'); }
  $id = (int)$_GET['id'];
  $cust = \App\CustomerRepo::findWithDetails($id);
  if (!$cust) { http_response_code(404); exit('Not Found'); }
  $technicians = \App\UserRepo::listByRoles(['LeitenderTechniker','Techniker']);
  $employees   = \App\UserRepo::listByRoles(['Admin','Projektleiter','LeitenderTechniker','Techniker','Mitarbeiter','Lernender']);
  render('customer_edit', ['c'=>$cust,'technicians'=>$technicians,'employees'=>$employees,'errors'=>[]]);
  break;


case 'customer_view':
  \App\Middleware::requireAuth();
  if (!isset($_GET['id'])) { http_response_code(400); exit('Fehlende ID'); }
  $id = (int)$_GET['id'];
  $cust = \App\CustomerRepo::findWithDetails($id);
  if (!$cust) { http_response_code(404); exit('Not Found'); }
  render('customer_view', ['c' => $cust]);
  break;

	case 'customer_update':
	  \App\Middleware::requireAuth();
	  if (!\App\Policy::can('customers.update')) { http_response_code(403); exit('Forbidden'); }
	  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }

	  $id = (int)($_GET['id'] ?? 0);
	  if ($id <= 0) { http_response_code(400); exit('Fehlende ID'); }

	  // Daten einsammeln wie beim Anlegen
	  $data = [
		'name'     => trim($_POST['name'] ?? ''),
		'street'   => trim($_POST['street'] ?? ''),
		'zip'      => trim($_POST['zip'] ?? ''),
		'city'     => trim($_POST['city'] ?? ''),
		'website'  => trim($_POST['website'] ?? ''),
		'logo_url' => trim($_POST['logo_url'] ?? ''),

		'maintenance_type'          => $_POST['maintenance_type'] ?? null,
		'maintenance_time'          => $_POST['maintenance_time'] ?? null,
		'maintenance_pause_reason' => trim($_POST['maintenance_pause_reason'] ?? ''),
		'maintenance_weekday'       => isset($_POST['maintenance_weekday']) ? (int)$_POST['maintenance_weekday'] : null,
		'maintenance_week_of_month' => isset($_POST['maintenance_week_of_month']) ? (int)$_POST['maintenance_week_of_month'] : null,
		'maintenance_year_month'    => isset($_POST['maintenance_year_month']) ? (int)$_POST['maintenance_year_month'] : null,
		'maintenance_year_day'      => isset($_POST['maintenance_year_day']) ? (int)$_POST['maintenance_year_day'] : null,

		'tech_id'  => (int)($_POST['responsible_technician_id'] ?? 0) ?: null,
		'owner_id' => (int)($_POST['owner_user_id'] ?? 0) ?: null,
	  ];
	  $contacts = $_POST['contacts'] ?? [];

	  $res = \App\CustomerRepo::update($id, $data, $contacts);
	  if ($res['ok']) { header('Location: ?route=customer_view&id='.$id); exit; }

	  // Fehlerfall: Formular erneut anzeigen
	  $cust = \App\CustomerRepo::findWithDetails($id);
	  $technicians = \App\UserRepo::listByRoles(['LeitenderTechniker','Techniker']);
	  $employees   = \App\UserRepo::listByRoles(['Admin','Projektleiter','LeitenderTechniker','Techniker','Mitarbeiter','Lernender']);
	  render('customer_edit', ['c'=>$cust,'technicians'=>$technicians,'employees'=>$employees,'errors'=>$res['errors']]);
	  break;
	  
	case 'customer_delete':
	  \App\Middleware::requireAuth();
	  if (!\App\Policy::can('customers.delete')) { http_response_code(403); exit('Forbidden'); }
	  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }

	  $id = (int)($_GET['id'] ?? 0);
	  if ($id <= 0) { http_response_code(400); exit('Bad Request'); }

	  $ok = \App\CustomerRepo::delete($id);
	  if ($ok) { header('Location: ?route=customers'); exit; }

	  http_response_code(500); echo 'Kunde konnte nicht gelöscht werden.'; exit;


/* ---------- Systeme ---------- */
case 'system_new':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('systems.create')) { http_response_code(403); exit('Forbidden'); }
  $customerId = (int)($_GET['customer_id'] ?? 0);
  if ($customerId<=0) { http_response_code(400); exit('Kunde fehlt'); }
  $technicians = \App\UserRepo::listByRoles(['LeitenderTechniker','Techniker']);
  $errors = [];
  render('system_new', compact('customerId','technicians','errors'));
  break;

case 'system_create':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('systems.create')) { http_response_code(403); exit('Forbidden'); }
  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
  $data = [
    'customer_id' => (int)($_POST['customer_id'] ?? 0),
    'name'        => trim($_POST['name'] ?? ''),
    'type'        => trim($_POST['type'] ?? ''),
    'role'        => trim($_POST['role'] ?? ''),
    'version'     => trim($_POST['version'] ?? ''),
    'install_date'=> ($_POST['install_date'] ?? '') ?: null,
    'responsible_technician_id' => (int)($_POST['responsible_technician_id'] ?? 0) ?: null,
    'notes'       => trim($_POST['notes'] ?? '')
  ];
  $res = \App\SystemRepo::create($data);
  if ($res['ok']) { header('Location: ?route=customer_view&id='.$data['customer_id']); exit; }
  $technicians = \App\UserRepo::listByRoles(['LeitenderTechniker','Techniker']);
  $errors = $res['errors'] ?? ['Unbekannter Fehler'];
  render('system_new', ['customerId'=>$data['customer_id'],'technicians'=>$technicians,'errors'=>$errors]);
  break;

/* ---------- Systeme: Bearbeiten ---------- */
case 'system_edit':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('systems.update')) { http_response_code(403); exit('Forbidden'); }
  $id = (int)($_GET['id'] ?? 0);
  $s  = \App\SystemRepo::find($id);
  if (!$s) { http_response_code(404); exit('Not Found'); }
  $technicians = \App\UserRepo::listByRoles(['LeitenderTechniker','Techniker']);
  $errors = [];
  render('system_edit', compact('s','technicians','errors'));
  break;

case 'system_update':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('systems.update')) { http_response_code(403); exit('Forbidden'); }
  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
  $id = (int)($_GET['id'] ?? 0);
  $s  = \App\SystemRepo::find($id);
  if (!$s) { http_response_code(404); exit('Not Found'); }
  $data = [
    'name'  => trim($_POST['name'] ?? ''),
    'type'  => trim($_POST['type'] ?? ''),
    'role'  => trim($_POST['role'] ?? ''),
    'version' => trim($_POST['version'] ?? ''),
    'install_date' => ($_POST['install_date'] ?? '') ?: null,
    'responsible_technician_id' => (int)($_POST['responsible_technician_id'] ?? 0) ?: null,
    'notes' => trim($_POST['notes'] ?? ''),
  ];
  $res = \App\SystemRepo::update($id, $data);
  if ($res['ok']) { header('Location: ?route=customer_view&id='.$s['customer_id']); exit; }
  $technicians = \App\UserRepo::listByRoles(['LeitenderTechniker','Techniker']);
  $errors = $res['errors'] ?? ['Unbekannter Fehler'];
  render('system_edit', ['s'=>array_merge($s,$data), 'technicians'=>$technicians, 'errors'=>$errors]);
  break;

case 'system_delete':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('systems.update')) { http_response_code(403); exit('Forbidden'); }
  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
  $id = (int)($_GET['id'] ?? 0);
  $s  = \App\SystemRepo::find($id);
  if ($s) {
    \App\SystemRepo::delete($id);
    // Tasks mit diesem system_id werden durch FK nicht gelöscht (SET NULL), also ok
    header('Location: ?route=customer_view&id='.$s['customer_id']); exit;
  }
  http_response_code(404); exit('Not Found');
  
/* ---------- Aufgaben ---------- */
case 'task_new':
  \App\Middleware::requireAuth();
  \App\Policy::enforce('tasks.create');
  $customer_id = (int)($_GET['customer_id'] ?? 0);
  $systems = \App\SystemRepo::listByCustomer($customer_id);
  $templates = class_exists('\App\TaskTemplateRepo') ? \App\TaskTemplateRepo::listAll() : [];
  $errors = [];
  render('task_edit', ['t'=>['customer_id'=>$customer_id],'systems'=>$systems,'templates'=>$templates,'errors'=>$errors]);
  break;


case 'task_create':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('tasks.create')) { http_response_code(403); exit('Forbidden'); }
  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
  $data = [
    'customer_id' => (int)($_POST['customer_id'] ?? 0),
    'system_id'   => (int)($_POST['system_id'] ?? 0) ?: null,
    'title'       => trim($_POST['title'] ?? ''),
    'status'      => $_POST['status'] ?? 'offen',
    'due_date'    => ($_POST['due_date'] ?? '') ?: null,
    'comment'     => trim($_POST['comment'] ?? ''),
	'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
	'is_paused'    => isset($_POST['is_paused']) ? 1 : 0,
	'pause_reason' => trim($_POST['pause_reason'] ?? ''),
    'created_by'  => (int)(\App\Auth::user()['id'] ?? 0) ?: null
  ];
  $res = \App\TaskRepo::create($data);
  if ($res['ok']) { header('Location: ?route=customer_view&id='.$data['customer_id']); exit; }
  $systems = \App\SystemRepo::listByCustomer($data['customer_id']);
  $errors = $res['errors'] ?? ['Unbekannter Fehler'];
  render('task_new', ['customerId'=>$data['customer_id'],'systemId'=>$data['system_id'],'systems'=>$systems,'errors'=>$errors]);
  break;

/* ---------- Aufgaben: Bearbeiten ---------- */
case 'task_edit':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('tasks.update')) { http_response_code(403); exit('Forbidden'); }
  $id = (int)($_GET['id'] ?? 0);
  $t  = \App\TaskRepo::find($id);
  if (!$t) { http_response_code(404); exit('Not Found'); }
  $systems = \App\SystemRepo::listByCustomer((int)$t['customer_id']);
  $errors = [];
  render('task_edit', compact('t','systems','errors'));
  break;

case 'task_update':
  \App\Middleware::requireAuth();
  if (!\App\Policy::can('tasks.update')) { http_response_code(403); exit('Forbidden'); }
  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }

	$id = (int)($_GET['id'] ?? 0);

	// id=0 bedeutet: neue Aufgabe anlegen
	if ($id === 0) {
	  $customerId = (int)($_POST['customer_id'] ?? 0);
	  if ($customerId <= 0) { http_response_code(400); exit('customer_id fehlt'); }

	  // Minimal anlegen (Repo muss ok+id zurückgeben)
	  $create = \App\TaskRepo::create([
		'customer_id'   => $customerId,
		'system_id'     => null, // wird unten via Multi-System gesetzt (oder du lässt es drin)
		'title'         => trim($_POST['title'] ?? ''),
		'status'        => $_POST['status'] ?? 'offen',
		'due_date'      => ($_POST['due_date'] ?? '') ?: null,
		'is_recurring'  => isset($_POST['is_recurring']) ? 1 : 0,
		'is_paused'     => isset($_POST['is_paused']) ? 1 : 0,
		'pause_reason'  => trim($_POST['pause_reason'] ?? ''),
		'comment'       => trim($_POST['comment'] ?? ''),
	  ]);

	  if (!($create['ok'] ?? false)) {
		http_response_code(400);
		exit('Fehler beim Erstellen: ' . implode('; ', $create['errors'] ?? ['Unbekannter Fehler']));
	  }

	  $id = (int)($create['id'] ?? 0);
	  if ($id <= 0) { http_response_code(500); exit('Create: keine ID erhalten'); }
	}

	$t  = \App\TaskRepo::find($id);
	if (!$t) { http_response_code(404); exit('Not Found'); }


  // 1) Aufgabe speichern
  $data = [
    'system_id'    => (int)($_POST['system_id'] ?? 0) ?: null,
    'title'        => trim($_POST['title'] ?? ''),
    'status'       => $_POST['status'] ?? 'offen',
    'due_date'     => ($_POST['due_date'] ?? '') ?: null,
    'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
    'is_paused'    => isset($_POST['is_paused']) ? 1 : 0,
    'pause_reason' => trim($_POST['pause_reason'] ?? ''),
    'comment'      => trim($_POST['comment'] ?? ''),
  ];
  $res = \App\TaskRepo::update($id, $data);
  // 2a) Status ins Log schreiben (Snapshot für PDF)
	try {
	  \App\TaskStatusLogRepo::log(
		$id,
		(string)($data['status'] ?? 'offen'),
		(string)($data['comment'] ?? ''),
		(int)(\App\Auth::user()['id'] ?? 0) ?: null
	  );
	} catch (\Throwable $e) {
	  error_log('TaskStatusLog error: '.$e->getMessage());
	}
  if (!$res['ok']) {
    $systems = \App\SystemRepo::listByCustomer((int)$t['customer_id']);
    $errors  = $res['errors'] ?? ['Unbekannter Fehler'];
    render('task_edit', ['t'=>array_merge($t,$data),'systems'=>$systems,'errors'=>$errors]);
  }

  // 2) Kontrollpunkte synchronisieren (falls vorhanden)
  if (class_exists('\App\TaskCheckpointRepo')) {
    $cpPayload = [
      'ids'    => $_POST['cp_id']    ?? [],
      'labels' => $_POST['cp_label'] ?? [],
      'is_done'=> $_POST['cp_done']  ?? [],
      'require_comment_on_fail' => $_POST['cp_reqcmt'] ?? [],
      'comments' => $_POST['cp_comment'] ?? [],
      'orders'   => $_POST['cp_order']   ?? [],
    ];
    $cpRes = \App\TaskCheckpointRepo::sync($id, $cpPayload);
    if (!$cpRes['ok']) {
      $t2 = \App\TaskRepo::find($id);
      $systems = \App\SystemRepo::listByCustomer((int)$t2['customer_id']);
      $errors  = $cpRes['errors'] ?? ['Fehler beim Speichern der Kontrollpunkte.'];
      $checkpoints = $cpPayload;
      render('task_edit', compact('t2','systems','errors','checkpoints'));
    }
  }

  // 3) Wiederkehrende Aufgabe? Sofort nächste Fälligkeit setzen
  $tNow = \App\TaskRepo::find($id);
  if ($tNow && ($tNow['status'] ?? '') === 'erledigt' && !empty($tNow['is_recurring']) && empty($tNow['is_paused'])) {
    $cst = \App\DB::pdo()->prepare("
      SELECT maintenance_type, maintenance_time, maintenance_weekday,
             maintenance_week_of_month, maintenance_year_month, maintenance_year_day
      FROM customers WHERE id=?
    ");
    $cst->execute([(int)$tNow['customer_id']]);
    $c = $cst->fetch() ?: null;

    if ($c) {
      $cx   = array_merge($tNow, $c);
      $next = \App\TaskRepo::nextFromCustomer($cx);
      if ($next) {
        // Falls next <= now (z.B. 00:00 schon vorbei), eine Periode vorspulen
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Zurich'));
        if ($next <= $now) {
          $next = \App\TaskRepo::rollForward($next, (string)($c['maintenance_type'] ?? 'daily'));
        }
        \App\DB::pdo()->prepare("UPDATE tasks SET status='offen', due_date=? WHERE id=? LIMIT 1")
          ->execute([$next->format('Y-m-d H:i:s'), $id]);
      }
    }
  }

  // 4) Fertig
  header('Location: ?route=customer_view&id='.$tNow['customer_id']); exit;


	case 'task_delete':
	  \App\Middleware::requireAuth();
	  if (!\App\Policy::can('tasks.update')) { http_response_code(403); exit('Forbidden'); }
	  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
	  $id = (int)($_GET['id'] ?? 0);
	  $t  = \App\TaskRepo::find($id);
	  if ($t) {
		\App\TaskRepo::delete($id);
		header('Location: ?route=customer_view&id='.$t['customer_id']); exit;
	  }
	  http_response_code(404); exit('Not Found');
	  break;
	
	case 'template_load':
	  \App\Middleware::requireAuth();
	  header('Content-Type: application/json');
	  $id = (int)($_GET['id'] ?? 0);
	  if ($id<=0 || !class_exists('\App\TaskTemplateRepo')) { echo json_encode(['ok'=>false]); exit; }
	  $tplSt = \App\DB::pdo()->prepare("SELECT * FROM task_templates WHERE id=?");
	  $tplSt->execute([$id]);
	  $tpl = $tplSt->fetch() ?: null;
	  $cps = \App\TaskTemplateRepo::checkpoints($id);
	  echo json_encode(['ok'=>true,'tpl'=>$tpl,'checkpoints'=>$cps]);
	  exit;
  
	case 'calendar':
	  \App\Middleware::requireAuth();
	  $tz = new \DateTimeZone('Europe/Zurich');
	  $start = new \DateTimeImmutable(($_GET['start'] ?? 'monday this week'), $tz);
	  $end   = new \DateTimeImmutable(($_GET['end']   ?? 'sunday this week 23:59:59'), $tz);
	  $events = \App\TaskRepo::listBetween($start, $end);
	  render('calendar', compact('events','start','end'));
	  break;

	case 'report_form':
	  \App\Middleware::requireAuth();
	  if (!\App\Policy::can('customers.view')) { http_response_code(403); exit('Forbidden'); }
	  $customer_id = (int)($_GET['customer_id'] ?? 0);
	  if ($customer_id <= 0) { http_response_code(400); exit('Bad Request'); }
	  render('report_form', compact('customer_id'));
	  break;

	// Report generieren
	case 'report_generate':
	  \App\Middleware::requireAuth();
	  if (!\App\Policy::can('customers.view')) { http_response_code(403); exit('Forbidden'); }
	  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }

	  $cid = (int)($_POST['customer_id'] ?? 0);
	  $to  = trim($_POST['to'] ?? ''); // datetime-local (Europe/Zurich)

	  // Validierung & Normalisierung
	  $tz = new \DateTimeZone('Europe/Zurich');
	  try {
		$toDt = $to !== '' ? new \DateTimeImmutable($to, $tz) : new \DateTimeImmutable('now', $tz);
	  } catch (\Throwable $e) {
		$toDt = new \DateTimeImmutable('now', $tz);
	  }
	  // Zukunft kappen (optional)
	  $now = new \DateTimeImmutable('now', $tz);
	  if ($toDt > $now) $toDt = $now;

	  $uid = (int)(\App\Auth::user()['id'] ?? 0);
	  $res = \App\ReportRepo::generateCustomerReport($cid, $uid, $toDt);

	  $redir = '?route=customer_view&id='.$cid;
	  header('Location: '.$redir.($res['ok'] ? '&msg=report_ok' : '&err='.urlencode(implode(', ',$res['errors'] ?? ['Fehler']))));
	  exit;

	/* ---------- Report Download ---------- */
	case 'report_download':
	  \App\Middleware::requireAuth();
	  $rid = (int)($_GET['id'] ?? 0);
	  $st = \App\DB::pdo()->prepare("SELECT r.file_path, r.filename, r.customer_id FROM customer_reports r WHERE r.id=? LIMIT 1");
	  $st->execute([$rid]);
	  $r = $st->fetch();
	  if (!$r || !is_file($r['file_path'])) { http_response_code(404); exit('Not Found'); }
	  header('Content-Type: application/pdf');
	  header('Content-Disposition: attachment; filename="'.basename($r['filename']).'"');
	  header('Content-Length: '.filesize($r['file_path']));
	  readfile($r['file_path']);
	  exit;

	/* ---------- Report löschen ---------- */
	case 'report_delete':
	  \App\Middleware::requireAuth();
	  if (!\App\Policy::can('customers.update')) { http_response_code(403); exit('Forbidden'); }
	  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
	  $rid = (int)($_GET['id'] ?? 0);
	  // für redirect Kundennummer holen
	  $st = \App\DB::pdo()->prepare("SELECT customer_id FROM customer_reports WHERE id=?");
	  $st->execute([$rid]);
	  $cid = (int)$st->fetchColumn();
	  if ($cid) \App\ReportRepo::delete($rid);
	  header('Location: ?route=customer_view&id='.$cid); exit;

	/* ---------- Dateien: Upload-Form ---------- */
	case 'file_new':
	  \App\Middleware::requireAuth();
	  if (!\App\Policy::can('files.upload')) { http_response_code(403); exit('Forbidden'); }
	  $customerId = (int)($_GET['customer_id'] ?? 0);
	  $systems = \App\SystemRepo::listByCustomer($customerId);
	  // Tasks optional laden (nur id/title)
	  $tst = \App\DB::pdo()->prepare("SELECT id, title FROM tasks WHERE customer_id=? ORDER BY id DESC LIMIT 500");
	  $tst->execute([$customerId]);
	  $tasks = $tst->fetchAll() ?: [];
	  $errors = [];
	  render('file_new', compact('customerId','systems','tasks','errors'));
	  break;

	/* ---------- Dateien: Upload speichern ---------- */
	case 'file_create':
	  \App\Middleware::requireAuth();
	  if (!\App\Policy::can('files.upload')) { http_response_code(403); exit('Forbidden'); }
	  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }

		$systemIds = array_filter(array_map('intval', $_POST['system_ids'] ?? []));
		$taskIds   = array_filter(array_map('intval', $_POST['task_ids']   ?? []));

		$data = [
		  'customer_id' => (int)($_POST['customer_id'] ?? 0),
		  'description' => trim($_POST['description'] ?? ''),
		  'uploaded_by' => (int)(\App\Auth::user()['id'] ?? 0) ?: null,
		  'system_ids'  => $systemIds,
		  'task_ids'    => $taskIds,
		];
		$res = \App\FileRepo::create($data, $_FILES['file'] ?? []);

	  if ($res['ok']) { header('Location: ?route=customer_view&id='.$data['customer_id']); exit; }

	  // on error, back to form
	  $systems = \App\SystemRepo::listByCustomer($data['customer_id']);
	  $tst = \App\DB::pdo()->prepare("SELECT id, title FROM tasks WHERE customer_id=? ORDER BY id DESC LIMIT 500");
	  $tst->execute([$data['customer_id']]);
	  $tasks = $tst->fetchAll() ?: [];
	  $errors = $res['errors'] ?? ['Fehler beim Upload'];
	  render('file_new', compact('customerId','systems','tasks','errors'));
	  break;

	/* ---------- Dateien: Download (Attachment) ---------- */
	case 'file_download':
	  \App\Middleware::requireAuth();
	  if (!\App\Policy::can('files.view')) { http_response_code(403); exit('Forbidden'); }
	  $id = (int)($_GET['id'] ?? 0);
	  $f = \App\FileRepo::find($id);
	  if (!$f) { http_response_code(404); exit('Not Found'); }
	  $path = \App\FileRepo::absolutePath($f);
	  if (!is_file($path)) { http_response_code(404); exit('Not Found'); }
	  header('Content-Type: application/octet-stream');
	  header('Content-Disposition: attachment; filename="'.basename($f['original_name']).'"');
	  header('Content-Length: '.filesize($path));
	  readfile($path);
	  exit;

	/* ---------- Dateien: Preview (Inline, nur Bilder/PDF) ---------- */
	case 'file_preview':
	  \App\Middleware::requireAuth();
	  if (!\App\Policy::can('files.view')) { http_response_code(403); exit('Forbidden'); }
	  $id = (int)($_GET['id'] ?? 0);
	  $f = \App\FileRepo::find($id);
	  if (!$f) { http_response_code(404); exit('Not Found'); }
	  $path = \App\FileRepo::absolutePath($f);
	  if (!is_file($path)) { http_response_code(404); exit('Not Found'); }

	  $mime = $f['mime'] ?: 'application/octet-stream';
	  $ext  = strtolower(pathinfo($f['original_name'], PATHINFO_EXTENSION));
	  $inlineOk = (str_starts_with($mime, 'image/') || $ext === 'pdf' || $mime === 'application/pdf');

	  header('Content-Type: '.$mime);
	  header('Content-Length: '.filesize($path));
	  header('Content-Disposition: '.($inlineOk ? 'inline' : 'attachment').'; filename="'.basename($f['original_name']).'"');
	  readfile($path);
	  exit;

	/* ---------- Dateien: Löschen ---------- */
	case 'file_delete':
	  \App\Middleware::requireAuth();
	  if (!\App\Policy::can('files.delete')) { http_response_code(403); exit('Forbidden'); }
	  if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
	  $id = (int)($_GET['id'] ?? 0);
	  $f  = \App\FileRepo::find($id);
	  if ($f) {
		\App\FileRepo::delete($id);
		header('Location: ?route=customer_view&id='.(int)$f['customer_id']); exit;
	  }
	  http_response_code(404); exit('Not Found');

	case 'dashboard_stats':
	  \App\Middleware::requireAuth();
	  if (!\App\Policy::can('admin.view')) { http_response_code(403); exit('Forbidden'); } // oder deine gewünschte Policy
	  $stats = \App\TaskRepo::stats();
	  render('dashboard_stats', compact('stats'));
	  break;


  default:
    http_response_code(404);
    echo '404 Not Found';
}
