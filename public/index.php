<?php
declare(strict_types=1);

use App\Auth;
use App\Config;
use App\Csrf;
use App\DB;
use App\Middleware;
use App\UserRepo;
use App\PasswordPolicy;

// Direktanzeige von Fehlern erzwingen (nur zum Debuggen!)
ini_set('display_errors', '0');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../error.log');
error_reporting(E_ALL);

require_once __DIR__.'/../src/Config.php';

// Ganz wichtig: Zuerst laden! 
\App\Config::load();

require_once __DIR__.'/../src/DB.php';
require_once __DIR__.'/../src/PasswordPolicy.php';
require_once __DIR__.'/../src/Csrf.php';
require_once __DIR__.'/../src/UserRepo.php';
require_once __DIR__.'/../src/Auth.php';
require_once __DIR__.'/../src/Middleware.php';
require_once __DIR__.'/../src/Policy.php';
require_once __DIR__.'/../src/ReportPdf.php';

// --- KORREKTUR: TCPDF HIER LADEN (VOR DEN REPOSITORIES) ---
// Wir suchen an allen möglichen Orten, damit es sicher gefunden wird
$tcpdf_paths = [
    __DIR__ . '/../src/tcpdf.php',              // 1. Priorität: Im src Ordner
    __DIR__ . '/../tcpdf.php',                  // 2. Priorität: Im Root
    __DIR__ . '/../vendor/tcpdf/tcpdf.php',     // 3. Priorität: Vendor
    __DIR__ . '/../lib/tcpdf/tcpdf.php'         // 4. Priorität: Lib
];
foreach ($tcpdf_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}
// ----------------------------------------------------------

require_once __DIR__.'/../src/CustomerRepo.php';
require_once __DIR__.'/../src/TaskRepo.php';
require_once __DIR__.'/../src/SystemRepo.php';
require_once __DIR__.'/../src/ReportRepo.php';
require_once __DIR__.'/../src/FileRepo.php';
require_once __DIR__.'/../src/TaskCheckpointRepo.php';
require_once __DIR__.'/../src/TaskStatusLogRepo.php';
require_once __DIR__.'/../src/ChangeLogRepo.php';
require_once __DIR__.'/../src/DashboardRepo.php';
require_once __DIR__ . '/../src/LicenseRepo.php';
require_once __DIR__ . '/../src/NetworkRepo.php';
require_once __DIR__ . '/../src/WikiRepo.php';
require_once __DIR__ . '/../src/Totp.php';

// Dann erst Auth und Logik
\App\Auth::start();

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

// --- ROUTING & LOGIN LOGIK ---
$route = $_GET['route'] ?? 'login';

// Prüfen ob eingeloggt aber 2FA noch ausstehend
if (isset($_SESSION['2fa_pending']) && $route !== 'login_2fa_check' && $route !== 'logout') {
    $route = 'login_2fa';
} else if (Auth::check()) {
    if ($route === 'login') $route = 'dashboard';
    $uid = (int)Auth::user()['id'];
    \App\DB::pdo()->prepare("UPDATE users SET last_seen_at = NOW() WHERE id = ?")->execute([$uid]);
} else {
    if ($route !== 'login_post') $route = 'login';
}

switch ($route) {
    case 'login':
        $flash = flash_take();
        render('login', compact('flash'));
        break;

    case 'login_post':
        if (!Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF'); }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $u = UserRepo::findByEmail($email);
        if ($u && password_verify($password, $u['password_hash'])) {
            if (($u['is_active']??1) == 0) { flash_set('error','Konto gesperrt.'); header('Location: ?route=login'); exit; }
            
            if (!empty($u['totp_secret'])) {
                $_SESSION['2fa_pending'] = $u['id'];
                header('Location: ?route=login_2fa'); 
                exit;
            } else {
                Auth::login($u);
                header('Location: ?route=dashboard'); exit;
            }
        }
        flash_set('error', 'Login fehlgeschlagen.');
        header('Location: ?route=login'); exit;

    case 'login_2fa':
        render('login_2fa');
        break;

    case 'login_2fa_check':
        if (!Csrf::check($_POST['csrf'] ?? null)) { die('CSRF'); }
        $uid = $_SESSION['2fa_pending'] ?? 0;
        $code = str_replace(' ', '', $_POST['code'] ?? '');
        
        if ($uid) {
            $u = UserRepo::findById($uid);
            if ($u && \App\Totp::verify($u['totp_secret'], $code)) {
                unset($_SESSION['2fa_pending']);
                Auth::login($u);
                header('Location: ?route=dashboard'); exit;
            }
        }
        flash_set('error', 'Falscher Code.');
        header('Location: ?route=login'); exit;

    case 'audit_log':
        \App\Middleware::requireAuth();
        if (!\App\Policy::hasRole(['Admin', 'Projektleiter'])) { die('Zugriff verweigert'); }
        $q = trim($_GET['q'] ?? '');
        $logs = \App\ChangeLogRepo::listGlobal(200, $q);
        render('audit_log', compact('logs', 'q'));
        break;

    case 'customer_wiki':
        \App\Middleware::requireAuth();
        $cid = (int)$_GET['id'];
        $c = \App\CustomerRepo::findWithDetails($cid);
        if(!$c) die("Kunde weg.");
        $entries = \App\WikiRepo::listByCustomer($cid);
        $entry = null;
        if(isset($_GET['edit'])) $entry = \App\WikiRepo::find((int)$_GET['edit']);
        render('customer_wiki', compact('c', 'entries', 'entry'));
        break;

    case 'wiki_save':
        \App\Middleware::requireAuth();
        if (!\App\Csrf::check($_POST['csrf'] ?? null)) die('CSRF');
        \App\WikiRepo::save((int)($_POST['id']??0), $_POST);
        header('Location: ?route=customer_wiki&id='.$_POST['customer_id']);
        exit;

    case 'wiki_delete':
        \App\Middleware::requireAuth();
        $id = (int)$_GET['id'];
        $w = \App\WikiRepo::find($id);
        if($w) {
            \App\WikiRepo::delete($id);
            header('Location: ?route=customer_wiki&id='.$w['customer_id']);
        }
        exit;

    case 'account_2fa':
        \App\Middleware::requireAuth();
        $u = Auth::user();
        $enabled = !empty($u['totp_secret']);
        $secret = $enabled ? '' : \App\Totp::generateSecret();
        $otpUrl = $enabled ? '' : "otpauth://totp/KMS:{$u['email']}?secret=$secret&issuer=KMS";
        render('account_2fa', compact('enabled', 'secret', 'otpUrl'));
        break;

    case 'account_2fa_enable':
        \App\Middleware::requireAuth();
        if (!\App\Csrf::check($_POST['csrf'] ?? null)) die('CSRF');
        $secret = $_POST['secret'];
        $code = str_replace(' ','',$_POST['code']);
        if (\App\Totp::verify($secret, $code)) {
            $uid = Auth::user()['id'];
            DB::pdo()->prepare("UPDATE users SET totp_secret = ? WHERE id = ?")->execute([$secret, $uid]);
            $_SESSION['user']['totp_secret'] = $secret;
            header('Location: ?route=account_2fa');
        } else {
            die("Code falsch. Bitte erneut versuchen.");
        }
        break;

    case 'account_2fa_disable':
        \App\Middleware::requireAuth();
        if (!\App\Csrf::check($_POST['csrf'] ?? null)) die('CSRF');
        $uid = Auth::user()['id'];
        DB::pdo()->prepare("UPDATE users SET totp_secret = NULL WHERE id = ?")->execute([$uid]);
        $_SESSION['user']['totp_secret'] = null;
        header('Location: ?route=account_2fa');
        break;

    case 'dashboard':
        \App\Middleware::requireAuth();
        $tasks = \App\TaskRepo::listOpenDue();
        $groups = \App\TaskRepo::groupByDueBuckets($tasks);
        render('dashboard', compact('groups'));
        break;

    case 'search_api':
        \App\Middleware::requireAuth();
        $q = trim($_GET['q'] ?? '');
        header('Content-Type: application/json');
        echo json_encode(\App\DashboardRepo::globalSearch($q));
        exit;

    case 'logout':
        Auth::logout();
        flash_set('success', 'Erfolgreich abgemeldet.');
        header('Location: ?route=login'); exit;

    case 'users':
        \App\Middleware::requireAuth();
        if (!\App\Policy::can('users.view')) { http_response_code(403); exit('Forbidden'); }
        $q    = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? 'email_asc';
        $users = \App\UserRepo::listAll($q, $sort);
        render('users_list', compact('users','q','sort'));
        break;

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
        
    case 'user_edit':
        \App\Middleware::requireAuth();
        if (!\App\Policy::can('users.manage')) { http_response_code(403); exit('Forbidden'); }
        $id = (int)($_GET['id'] ?? 0);
        $u  = \App\UserRepo::findById($id);
        if (!$u) { http_response_code(404); exit('Not Found'); }
        render('user_edit', ['u'=>$u, 'errors'=>[]]);
        break;

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

    case 'user_password':
        \App\Middleware::requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $target = \App\UserRepo::findById($id);
        if (!$target) { http_response_code(404); exit('Not Found'); }
        $actor = \App\Auth::user();
        if (!\App\Policy::canResetPasswordOf($actor, $target)) { http_response_code(403); exit('Forbidden'); }
        render('user_password', ['u'=>$target, 'errors'=>[]]);
        break;

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
        $u = null; 
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
        $sort = $_GET['sort'] ?? 'name_asc';
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
        $u = \App\Auth::user();
        $st = \App\DB::pdo()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $st->execute([$u['id']]);
        $hash = $st->fetchColumn();
        if (!$hash || !password_verify($current, $hash)) {
            $errors[] = 'Aktuelles Passwort ist falsch.';
        }
        $pwErrors = \App\PasswordPolicy::validate($new);
        $errors = array_merge($errors, $pwErrors);
        if ($new !== $confirm) $errors[] = 'Passwörter stimmen nicht überein.';
        if ($errors) {
            render('account_password', ['errors'=>$errors, 'flash'=>[]]);
            break;
        }
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $upd = \App\DB::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute([$newHash, $u['id']]);
        flash_set('success', 'Passwort wurde geändert.');
        header('Location: ?route=account'); exit;

    case 'customer_new':
        \App\Middleware::requireAuth();
        if (!\App\Policy::can('customers.create')) { http_response_code(403); exit('Forbidden'); }
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
            'password_manager_url' => trim($_POST['password_manager_url'] ?? '') ?: null,
            'remote_access_type'   => trim($_POST['remote_access_type'] ?? '') ?: null,
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
        $id = (int)($_GET['id'] ?? 0);
        $cust = \App\CustomerRepo::findWithDetails($id);
        if (!$cust) { http_response_code(404); exit('Not Found'); }
        $technicians = \App\UserRepo::listByRoles(['LeitenderTechniker','Techniker']);
        $employees   = \App\UserRepo::listByRoles(['Admin','Projektleiter','LeitenderTechniker','Techniker','Mitarbeiter','Lernender']);
        render('customer_edit', ['c'=>$cust,'technicians'=>$technicians,'employees'=>$employees,'errors'=>[]]);
        break;

    case 'customer_view':
        \App\Middleware::requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $cust = \App\CustomerRepo::findWithDetails($id);
        if (!$cust) { http_response_code(404); exit('Not Found'); }
        render('customer_view', ['c' => $cust]);
        break;

    case 'customer_update':
        \App\Middleware::requireAuth();
        if (!\App\Policy::can('customers.update')) { http_response_code(403); exit('Forbidden'); }
        if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
        $id = (int)($_GET['id'] ?? 0);
        $data = [
            'name'     => trim($_POST['name'] ?? ''),
            'street'   => trim($_POST['street'] ?? ''),
            'zip'      => trim($_POST['zip'] ?? ''),
            'city'     => trim($_POST['city'] ?? ''),
            'website'  => trim($_POST['website'] ?? ''),
            'logo_url' => trim($_POST['logo_url'] ?? ''),
            'password_manager_url' => trim($_POST['password_manager_url'] ?? '') ?: null,
            'remote_access_type'   => trim($_POST['remote_access_type'] ?? '') ?: null,
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
        $ok = \App\CustomerRepo::delete($id);
        if ($ok) { header('Location: ?route=customers'); exit; }
        http_response_code(500); echo 'Fehler beim Löschen'; exit;

    case 'system_new':
        \App\Middleware::requireAuth();
        if (!\App\Policy::can('systems.create')) { http_response_code(403); exit('Forbidden'); }
        $customerId = (int)($_GET['customer_id'] ?? 0);
        $technicians = \App\UserRepo::listByRoles(['LeitenderTechniker','Techniker']);
        render('system_new', ['customerId'=>$customerId,'technicians'=>$technicians,'errors'=>[]]);
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
        render('system_new', ['customerId'=>$data['customer_id'],'technicians'=>$technicians,'errors'=>$res['errors']]);
        break;

    case 'system_edit':
        \App\Middleware::requireAuth();
        if (!\App\Policy::can('systems.update')) { http_response_code(403); exit('Forbidden'); }
        $id = (int)($_GET['id'] ?? 0);
        $s  = \App\SystemRepo::find($id);
        if (!$s) { http_response_code(404); exit('Not Found'); }
        $technicians = \App\UserRepo::listByRoles(['LeitenderTechniker','Techniker']);
        render('system_edit', ['s'=>$s,'technicians'=>$technicians,'errors'=>[]]);
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
        render('system_edit', ['s'=>array_merge($s,$data), 'technicians'=>$technicians, 'errors'=>$res['errors']]);
        break;

    case 'system_delete':
        \App\Middleware::requireAuth();
        if (!\App\Policy::can('systems.update')) { http_response_code(403); exit('Forbidden'); }
        if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
        $id = (int)($_GET['id'] ?? 0);
        $s  = \App\SystemRepo::find($id);
        if ($s) {
            \App\SystemRepo::delete($id);
            header('Location: ?route=customer_view&id='.$s['customer_id']); exit;
        }
        http_response_code(404); exit('Not Found');

    case 'task_new':
        \App\Middleware::requireAuth();
        \App\Policy::enforce('tasks.create');
        require_once __DIR__.'/../views/task_edit.php';
        break;

    case 'task_edit':
        \App\Middleware::requireAuth();
        \App\Policy::enforce('tasks.update');
        require_once __DIR__.'/../views/task_edit.php';
        break;

    case 'task_create':
        \App\Middleware::requireAuth();
        \App\Policy::enforce('tasks.create');
        if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
        $data = [
            'customer_id' => (int)($_POST['customer_id'] ?? 0),
            'system_id'   => (int)($_POST['system_id'] ?? 0) ?: null,
            'title'       => trim($_POST['title'] ?? ''),
            'status'      => $_POST['status'] ?? 'offen',
            'due_date'    => ($_POST['due_date'] ?? '') ?: null,
            'is_recurring'=> isset($_POST['is_recurring']) ? 1 : 0,
            'is_paused'   => isset($_POST['is_paused']) ? 1 : 0,
            'pause_reason'=> trim($_POST['pause_reason'] ?? ''),
            'comment'     => trim($_POST['comment'] ?? ''),
            'created_by'  => (int)(\App\Auth::user()['id'] ?? 0) ?: null
        ];
        if ($data['customer_id'] <= 0) die("Fehler: Kunde fehlt.");
        $res = \App\TaskRepo::create($data);
        if ($res['ok']) {
            header('Location: ?route=task_edit&id=' . (int)$res['id']);
            exit;
        } else {
            die("Fehler beim Erstellen: " . implode(', ', $res['errors']));
        }
        break;

    case 'task_update':
        \App\Middleware::requireAuth();
        \App\Policy::enforce('tasks.update');
        if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
        $id = (int)($_GET['id'] ?? 0);
        $t = \App\TaskRepo::find($id);
        if (!$t) { http_response_code(404); exit('Not Found'); }
        $data = [
            'system_id'   => (int)($_POST['system_id'] ?? 0) ?: null,
            'title'       => trim($_POST['title'] ?? ''),
            'status'      => $_POST['status'] ?? 'offen',
            'due_date'    => ($_POST['due_date'] ?? '') ?: null,
            'is_recurring'=> isset($_POST['is_recurring']) ? 1 : 0,
            'is_paused'   => isset($_POST['is_paused']) ? 1 : 0,
            'pause_reason'=> trim($_POST['pause_reason'] ?? ''),
            'comment'     => trim($_POST['comment'] ?? ''),
            'time_spent_minutes' => (int)($_POST['time_spent_minutes'] ?? 0)
        ];
        $res = \App\TaskRepo::update($id, $data);
        if (class_exists('\App\TaskStatusLogRepo')) {
            try { \App\TaskStatusLogRepo::log($id, $data['status'], $data['comment'], (int)(\App\Auth::user()['id'] ?? 0)); } catch (\Throwable $e) {}
        }
        if (class_exists('\App\TaskCheckpointRepo')) {
            $cpRaw = $_POST['cp'] ?? [];
            $cpPayload = [
                'ids'      => $cpRaw['ids'] ?? [],
                'labels'   => $cpRaw['labels'] ?? [],
                'is_done'  => $cpRaw['is_done'] ?? [],
                'require_comment_on_fail' => $cpRaw['require_comment_on_fail'] ?? [],
                'comments' => $cpRaw['comments'] ?? [],
                'orders'   => $cpRaw['orders'] ?? [],
            ];
            \App\TaskCheckpointRepo::sync($id, $cpPayload);
        }
        if (!$res['ok']) { die("Fehler beim Speichern: " . implode(', ', $res['errors'])); }
        header('Location: ?route=customer_view&id=' . (int)$t['customer_id']);
        exit;
        break;

    case 'task_delete':
        \App\Middleware::requireAuth();
        \App\Policy::enforce('tasks.update');
        if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
        $id = (int)($_GET['id'] ?? 0);
        $t  = \App\TaskRepo::find($id);
        if ($t) {
            \App\TaskRepo::delete($id);
            header('Location: ?route=customer_view&id=' . $t['customer_id']);
            exit;
        }
        http_response_code(404); exit('Not Found');
        break;

    case 'tasks_global':
        \App\Middleware::requireAuth();
        require_once __DIR__.'/../views/tasks_global.php';
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

    // --- REPORTS (MIT FIX FÜR URL-PARAMETER) ---
    case 'report_form':
        \App\Middleware::requireAuth();
        if (!\App\Policy::can('customers.view')) { http_response_code(403); exit('Forbidden'); }
        $customer_id = (int)($_GET['customer_id'] ?? 0);
        if ($customer_id <= 0) { http_response_code(400); exit('Customer ID fehlt'); }
        render('report_form', compact('customer_id'));
        break;

    case 'report_generate':
        \App\Middleware::requireAuth();
        if (!\App\Policy::can('customers.view')) { http_response_code(403); exit('Forbidden'); }
        if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
        $cid = (int)($_POST['customer_id'] ?? 0);
        $tz = new \DateTimeZone('Europe/Zurich');
        try {
            $rDateStr = $_POST['report_date'] ?? 'now';
            $fromDateStr = $_POST['from_date'] ?? '-1 month';
            $toDateStr = $_POST['to_date'] ?? 'now';
            $rDate = new \DateTimeImmutable($rDateStr ?: 'now', $tz);
            $from  = new \DateTimeImmutable($fromDateStr ?: '-1 month', $tz);
            $to    = new \DateTimeImmutable($toDateStr ?: 'now', $tz);
        } catch (\Throwable $e) {
            die("Fehler beim Datumsformat: " . $e->getMessage());
        }
        $comment = trim($_POST['comment'] ?? '');
        $files   = $_FILES['attachments'] ?? [];
        $uid = (int)(\App\Auth::user()['id'] ?? 0);
        
        $res = \App\ReportRepo::generateCustomerReport($cid, $uid, $rDate, $from, $to, $comment, $files);
        
        // FIX: URL-Parameter msg/err anhängen
        $qs = $res['ok'] ? '&msg=report_ok' : '&err='.urlencode(implode(', ',$res['errors']??[]));
        header('Location: ?route=customer_view&id='.$cid . $qs);
        exit;

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

    case 'report_delete':
        \App\Middleware::requireAuth();
        if (!\App\Policy::can('customers.update')) { http_response_code(403); exit('Forbidden'); }
        if (!\App\Csrf::check($_POST['csrf'] ?? null)) { http_response_code(400); exit('CSRF ungültig'); }
        $rid = (int)($_GET['id'] ?? 0);
        $st = \App\DB::pdo()->prepare("SELECT customer_id FROM customer_reports WHERE id=?");
        $st->execute([$rid]);
        $cid = (int)$st->fetchColumn();
        if ($cid) \App\ReportRepo::delete($rid);
        header('Location: ?route=customer_view&id='.$cid); exit;

    // --- DATEIEN ---
    case 'file_new':
        \App\Middleware::requireAuth();
        if (!\App\Policy::can('files.upload')) { http_response_code(403); exit('Forbidden'); }
        $customerId = (int)($_GET['customer_id'] ?? 0);
        $systems = \App\SystemRepo::listByCustomer($customerId);
        $tst = \App\DB::pdo()->prepare("SELECT id, title FROM tasks WHERE customer_id=? ORDER BY id DESC LIMIT 500");
        $tst->execute([$customerId]);
        $tasks = $tst->fetchAll() ?: [];
        $errors = [];
        render('file_new', compact('customerId','systems','tasks','errors'));
        break;

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
        $systems = \App\SystemRepo::listByCustomer($data['customer_id']);
        $tst = \App\DB::pdo()->prepare("SELECT id, title FROM tasks WHERE customer_id=? ORDER BY id DESC LIMIT 500");
        $tst->execute([$data['customer_id']]);
        $tasks = $tst->fetchAll() ?: [];
        $errors = $res['errors'] ?? ['Fehler beim Upload'];
        render('file_new', compact('customerId','systems','tasks','errors'));
        break;

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
        if (!\App\Policy::can('admin.view')) { http_response_code(403); exit('Forbidden'); }
        $stats = \App\TaskRepo::stats();
        render('dashboard_stats', compact('stats'));
        break;

    case 'changelog_add':
        \App\Middleware::requireAuth();
        $cid = (int)$_POST['customer_id'];
        $note = trim($_POST['note'] ?? '');
        if ($cid > 0 && $note !== '') {
            \App\ChangeLogRepo::log($cid, 'manual', 'Notiz', $note);
        }
        header('Location: ?route=customer_view&id='.$cid);
        exit;

    case 'changelog_export':
        \App\Middleware::requireAuth();
        $cid = (int)$_GET['customer_id'];
        $logs = \App\ChangeLogRepo::list($cid);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=changelog_'.$cid.'_'.date('Ymd').'.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Datum', 'Mitarbeiter', 'Typ', 'Aktion', 'Notiz', 'Link']);
        foreach ($logs as $l) {
            fputcsv($out, [$l['created_at'], $l['user_email'] ?? 'System', $l['entity_type'], $l['action_type'], $l['note'], $l['ref_link']]);
        }
        fclose($out);
        exit;

    case 'changelog_clear':
        \App\Middleware::requireAuth();
        $cid = (int)$_GET['customer_id'];
        if (\App\ChangeLogRepo::clear($cid)) {
            \App\ChangeLogRepo::log($cid, 'log', 'clear', 'Protokoll durch Admin bereinigt.');
        }
        header('Location: ?route=customer_view&id='.$cid);
        exit;

    case 'customer_licenses':
        \App\Middleware::requireAuth();
        $cid = (int)($_GET['id'] ?? 0);
        $c = \App\CustomerRepo::findWithDetails($cid);
        if (!$c) die("Kunde nicht gefunden");
        $licenses = \App\LicenseRepo::listByCustomer($cid);
        render('customer_licenses', ['c'=>$c, 'licenses'=>$licenses]);
        break;

    case 'license_create':
        \App\Middleware::requireAuth();
        \App\Policy::enforce('customers.update');
        \App\LicenseRepo::create($_POST);
        header('Location: ?route=customer_licenses&id=' . (int)$_POST['customer_id']);
        exit;
        break;

    case 'license_delete':
        \App\Middleware::requireAuth();
        \App\Policy::enforce('customers.update');
        $id = (int)$_GET['id'];
        $pdo = \App\DB::pdo(); 
        $stmt = $pdo->prepare("SELECT customer_id FROM customer_licenses WHERE id=?"); 
        $stmt->execute([$id]); 
        $cid = $stmt->fetchColumn();
        \App\LicenseRepo::delete($id);
        header('Location: ?route=customer_licenses&id=' . (int)$cid);
        exit;
        break;

    case 'license_export':
        \App\Middleware::requireAuth();
        $cid = (int)$_GET['customer_id'];
        \App\LicenseRepo::exportCsv($cid);
        exit;
        break;

    case 'license_import':
        \App\Middleware::requireAuth();
        \App\Policy::enforce('customers.update');
        $cid = (int)$_POST['customer_id'];
        \App\LicenseRepo::importCsv($cid, $_FILES['csv_file']);
        header('Location: ?route=customer_licenses&id=' . (int)$cid);
        exit;
        break;

    case 'customer_networks':
        \App\Middleware::requireAuth();
        $cid = (int)($_GET['id'] ?? 0);
        $c = \App\CustomerRepo::findWithDetails($cid);
        if (!$c) die("Kunde nicht gefunden");
        $networks = \App\NetworkRepo::listByCustomer($cid);
        render('customer_networks', ['c'=>$c, 'networks'=>$networks]);
        break;

    case 'network_create':
        \App\Middleware::requireAuth();
        \App\Policy::enforce('customers.update');
        \App\NetworkRepo::create($_POST);
        header('Location: ?route=customer_networks&id=' . (int)$_POST['customer_id']);
        exit;
        break;

    case 'network_delete':
        \App\Middleware::requireAuth();
        \App\Policy::enforce('customers.update');
        $id = (int)$_GET['id'];
        $pdo = \App\DB::pdo(); 
        $stmt = $pdo->prepare("SELECT customer_id FROM customer_networks WHERE id=?"); 
        $stmt->execute([$id]); 
        $cid = $stmt->fetchColumn();
        \App\NetworkRepo::delete($id);
        header('Location: ?route=customer_networks&id=' . (int)$cid);
        exit;
        break;

    default:
        http_response_code(404);
        echo '404 Not Found';
}