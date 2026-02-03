<?php
namespace App;

use App\DB;
use PDO;

class TaskRepo {
	
	public static function create(array $data): array {
	  $errors = [];
	  if (($data['customer_id'] ?? 0) <= 0) $errors[] = 'Kunde fehlt.';
	  if (trim($data['title'] ?? '') === '') $errors[] = 'Titel ist Pflicht.';
	  
	  $st = DB::pdo()->prepare("
		INSERT INTO tasks (customer_id, system_id, title, status, is_paused, pause_reason, is_recurring, due_date, comment, created_by)
		VALUES (?,?,?,?,?,?,?,?,?,?)
	  ");
	  $st->execute([
		(int)$data['customer_id'],
		(int)($data['system_id'] ?? 0) ?: null,
		trim($data['title']),
		$data['status'] ?? 'offen',
		(int)($data['is_paused'] ?? 0),
		trim($data['pause_reason'] ?? '') ?: null,
		(int)($data['is_recurring'] ?? 1),
		$data['due_date'] ?: null,
		trim($data['comment'] ?? '') ?: null,
		(int)($data['created_by'] ?? 0) ?: null
	  ]);
      $newId = (int)DB::pdo()->lastInsertId();

      if (class_exists('\App\ChangeLogRepo')) {
          \App\ChangeLogRepo::log((int)$data['customer_id'], 'task', 'create', "Aufgabe erstellt: ".trim($data['title']));
      }
	  return ['ok'=>true,'id'=>$newId];
	}

    public static function listByCustomer(int $customerId): array {
      $st = DB::pdo()->prepare("SELECT t.*, s.name AS system_name FROM tasks t LEFT JOIN systems s ON s.id = t.system_id WHERE t.customer_id = ? ORDER BY COALESCE(t.due_date, '9999-12-31') ASC, t.id DESC");
      $st->execute([$customerId]);
      return $st->fetchAll() ?: [];
    }

    // NEU: Globale Liste für das Dashboard-Link-Ziel
    public static function listGlobal(string $filter = 'all', int $userId = 0): array {
        $pdo = DB::pdo();
        $sql = "SELECT t.*, c.name AS customer_name, s.name AS system_name 
                FROM tasks t 
                JOIN customers c ON c.id = t.customer_id 
                LEFT JOIN systems s ON s.id = t.system_id 
                WHERE t.status IN ('offen', 'ausstehend')";
        
        $params = [];
        if ($filter === 'overdue') {
            $sql .= " AND t.due_date < CURDATE()";
        } elseif ($filter === 'mine' && $userId > 0) {
            $sql .= " AND (c.responsible_technician_id = ? OR c.owner_user_id = ?)";
            $params[] = $userId;
            $params[] = $userId;
        }

        $sql .= " ORDER BY t.due_date ASC, c.name ASC";
        
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

	public static function listOpenDue(int $limit = 600): array {
	  $st = DB::pdo()->prepare("
		SELECT t.id, t.title, t.status, t.due_date, t.is_paused, t.is_recurring, t.customer_id, t.system_id,
		  c.maintenance_type, c.maintenance_time, c.maintenance_weekday, c.maintenance_week_of_month, c.maintenance_year_month, c.maintenance_year_day,
		  c.name AS customer_name, s.name AS system_name
		FROM tasks t JOIN customers c ON c.id = t.customer_id LEFT JOIN systems s ON s.id = t.system_id
		WHERE t.status IN ('offen','ausstehend') ORDER BY t.id DESC LIMIT :lim
	  ");
	  $st->bindValue(':lim', $limit, PDO::PARAM_INT);
	  $st->execute();
	  return $st->fetchAll() ?: [];
	}

    public static function find(int $id): ?array {
      $st = DB::pdo()->prepare("SELECT * FROM tasks WHERE id = ? LIMIT 1");
      $st->execute([$id]);
      return $st->fetch() ?: null;
    }

	public static function update(int $id, array $data): array {
	  $errors = [];
	  if (trim($data['title'] ?? '') === '') $errors[] = 'Titel ist Pflicht.';
      $currentTask = self::find($id);

	  $st = DB::pdo()->prepare("
		UPDATE tasks SET
		  system_id=?, title=?, status=?, is_paused=?, pause_reason=?, is_recurring=?, due_date=?, comment=?, time_spent_minutes=?
		WHERE id=? LIMIT 1
	  ");
	  $st->execute([
		(int)($data['system_id'] ?? 0) ?: null,
		trim($data['title']),
		$data['status'],
		(int)($data['is_paused'] ?? 0),
		trim($data['pause_reason'] ?? '') ?: null,
		(int)($data['is_recurring'] ?? 1),
		$data['due_date'] ?: null,
		trim($data['comment'] ?? '') ?: null,
        (int)($data['time_spent_minutes'] ?? 0),
		$id
	  ]);

      if ($currentTask && class_exists('\App\ChangeLogRepo')) {
          $cid = (int)$currentTask['customer_id'];
          $msg = "Aufgabe '{$data['title']}' bearbeitet.";
          if ($currentTask['status'] !== $data['status']) $msg .= " Status: {$currentTask['status']} -> {$data['status']}.";
          \App\ChangeLogRepo::log($cid, 'task', 'update', $msg);
      }
	  return ['ok'=>true];
	}

    // UPDATE: Neuer Parameter $ignoreTime für Cronjob
	public static function nextFromCustomer(array $c, bool $ignoreTime = false): ?\DateTimeImmutable { 
	  date_default_timezone_set('Europe/Zurich');
	  $now = new \DateTimeImmutable('now');
	  $type = $c['maintenance_type'] ?? null;
	  if (!$type || $type==='none' || $type==='paused') return null;
	  $time = $c['maintenance_time'] ?? null;
	  $hh = $mm = 0;
	  if ($time) { [$hh,$mm] = array_map('intval', explode(':', $time, 3)); }

	  switch ($type) {
		case 'daily': 
            $today = (new \DateTimeImmutable('today'))->setTime($hh, $mm, 0); 
            if ($ignoreTime) return $today; // Für Cron: Gib immer Heute zurück
            return $now <= $today ? $today : $today->modify('+1 day');
            
		case 'weekly': case 'biweekly': 
		  $wd = max(1, min(7, (int)($c['maintenance_weekday'] ?? 1))); 
          $todayW = (int)(new \DateTimeImmutable('now'))->format('N');
		  $base = (new \DateTimeImmutable('today'))->setTime($hh,$mm,0);
		  $diff = $wd - $todayW; 
          $candidate = $base->modify(($diff >= 0 ? "+$diff days" : "$diff days"));
		  
          // Wenn Zeit egal (Cron), nehmen wir den Kandidaten dieser Woche, auch wenn vorbei
          if (!$ignoreTime && $candidate <= $now) $candidate = $candidate->modify('+1 week');
		  
          $parNow = ((int)$now->format('W')) % 2; $parCand = ((int)$candidate->format('W')) % 2;
		  if ($parNow === $parCand) $candidate = $candidate->modify('+1 week');
		  return $candidate;
          
		case 'monthly':
		  $wom = (int)($c['maintenance_week_of_month'] ?? 1); $wd = max(1, min(7, (int)($c['maintenance_weekday'] ?? 1)));
		  $ref = new \DateTimeImmutable('first day of this month '.$hh.':'.$mm.':00');
		  $candidate = self::nthWeekdayOfMonth($ref, $wom, $wd);
		  if (!$ignoreTime && $candidate <= $now) { $refNext = $ref->modify('first day of next month'); $candidate = self::nthWeekdayOfMonth($refNext, $wom, $wd); }
		  return $candidate;
          
		case 'yearly':
		  $m = (int)($c['maintenance_year_month'] ?? 0); $d = (int)($c['maintenance_year_day'] ?? 0);
		  if ($m<1 || $d<1) return null;
		  $year = (int)date('Y'); $cand = (new \DateTimeImmutable("$year-$m-$d"))->setTime($hh,$mm,0);
		  if (!$ignoreTime && $cand <= $now) $cand = $cand->modify('+1 year');
		  return $cand;
	  }
	  return null;
    }

    public static function rollForward(\DateTimeImmutable $dt, string $type): \DateTimeImmutable {
	  switch ($type) {
		case 'daily': return $dt->modify('+1 day');
		case 'weekly': return $dt->modify('+1 week');
		case 'biweekly': return $dt->modify('+2 weeks');
		case 'monthly': return $dt->modify('+1 month');
		case 'yearly': return $dt->modify('+1 year');
	  }
	  return $dt;
	}

	private static function nthWeekdayOfMonth(\DateTimeImmutable $firstOfMonth, int $nth, int $weekday): \DateTimeImmutable {
	  $month = (int)$firstOfMonth->format('m'); $year = (int)$firstOfMonth->format('Y');
	  if ($nth === -1) {
		$last = new \DateTimeImmutable("last day of $year-$month");
		while ((int)$last->format('N') !== $weekday) $last = $last->modify('-1 day');
		return $last->setTime((int)$firstOfMonth->format('H'), (int)$firstOfMonth->format('i'), 0);
	  }
	  $d = new \DateTimeImmutable("first day of $year-$month");
	  while ((int)$d->format('N') !== $weekday) $d = $d->modify('+1 day');
	  $d = $d->modify('+' . ($nth-1) . ' weeks');
	  return $d->setTime((int)$firstOfMonth->format('H'), (int)$firstOfMonth->format('i'), 0);
	}

    public static function delete(int $id): bool {
      $task = self::find($id);
      $st = DB::pdo()->prepare("DELETE FROM tasks WHERE id = ? LIMIT 1");
      $ok = $st->execute([$id]);
      if ($ok && $task && class_exists('\App\ChangeLogRepo')) {
          \App\ChangeLogRepo::log((int)$task['customer_id'], 'task', 'delete', "Aufgabe gelöscht: {$task['title']}");
      }
      return $ok;
    }

	public static function groupByDueBuckets(array $tasks): array {
	  date_default_timezone_set('Europe/Zurich');
	  $today = new \DateTimeImmutable('today'); $tomorrow = $today->modify('+1 day');
	  $weekday = (int)$today->format('w'); $daysToSunday = (7 - $weekday) % 7;
	  $endOfWeek = $today->modify("+{$daysToSunday} days")->setTime(23,59,59);
	  $groups = ['overdue'=>[], 'today'=>[], 'tomorrow'=>[], 'thisweek'=>[]];
	  foreach ($tasks as $t) {
		if (!empty($t['is_paused']) && empty($t['due_date'])) continue;
		$eff = null;
		if (!empty($t['due_date'])) $eff = new \DateTimeImmutable($t['due_date']);
		elseif (!empty($t['is_recurring'])) $eff = self::nextFromCustomer($t);
		if (!$eff) continue;
		if ($eff < $today) { $t['effective_due'] = $eff; $groups['overdue'][] = $t; }
		elseif ($eff < $tomorrow) { $t['effective_due'] = $eff; $groups['today'][] = $t; }
		elseif ($eff < $tomorrow->modify('+1 day')) { $t['effective_due'] = $eff; $groups['tomorrow'][] = $t; }
		elseif ($eff <= $endOfWeek) { $t['effective_due'] = $eff; $groups['thisweek'][] = $t; }
	  }
	  foreach ($groups as &$g) { usort($g, fn($a,$b) => $a['effective_due'] <=> $b['effective_due']); }
	  return $groups;
	}
}