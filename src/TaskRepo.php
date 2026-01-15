<?php
namespace App;

class TaskRepo {
	
	public static function create(array $data): array {
	  $errors = [];
	  if (($data['customer_id'] ?? 0) <= 0) $errors[] = 'Kunde fehlt.';
	  if (trim($data['title'] ?? '') === '') $errors[] = 'Titel ist Pflicht.';
	  if (!in_array($data['status'] ?? 'offen', ['offen','ausstehend','erledigt'], true)) $errors[] = 'Ungültiger Status.';
	  if (!empty($data['is_paused']) && empty($data['pause_reason'])) $errors[] = 'Bitte Pausierungsgrund angeben.';
	  if ($errors) return ['ok'=>false,'errors'=>$errors];

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
	  return ['ok'=>true,'id'=>(int)DB::pdo()->lastInsertId()];
	}

public static function listByCustomer(int $customerId): array {
  $st = DB::pdo()->prepare("
    SELECT t.*, s.name AS system_name
    FROM tasks t
    LEFT JOIN systems s ON s.id = t.system_id
    WHERE t.customer_id = ?
    ORDER BY COALESCE(t.due_date, '9999-12-31') ASC, t.id DESC
  ");
  $st->execute([$customerId]);
  return $st->fetchAll() ?: [];
}

  /**
   * Offene/ausstehende Aufgaben mit Fälligkeit laden (inkl. Kunde/System).
   */
	public static function listOpenDue(int $limit = 600): array {
	  $sql = "
		SELECT
		  t.id, t.title, t.status, t.due_date, t.is_paused, t.is_recurring,
		  t.customer_id, t.system_id,
		  c.maintenance_type, c.maintenance_time, c.maintenance_weekday,
		  c.maintenance_week_of_month, c.maintenance_year_month, c.maintenance_year_day,
		  c.name AS customer_name,
		  s.name AS system_name
		FROM tasks t
		JOIN customers c ON c.id = t.customer_id
		LEFT JOIN systems   s ON s.id = t.system_id
		WHERE t.status IN ('offen','ausstehend')
		ORDER BY t.id DESC
		LIMIT :lim
	  ";
	  $st = DB::pdo()->prepare($sql);
	  $st->bindValue(':lim', $limit, \PDO::PARAM_INT);
	  $st->execute();
	  return $st->fetchAll() ?: [];
	}

	public static function listBetween(\DateTimeInterface $from, \DateTimeInterface $to): array {
	  $st = DB::pdo()->prepare("
		SELECT t.*, c.name AS customer_name, s.name AS system_name
		FROM tasks t
		JOIN customers c ON c.id = t.customer_id
		LEFT JOIN systems s ON s.id = t.system_id
		WHERE t.due_date IS NOT NULL AND t.due_date BETWEEN ? AND ?
		ORDER BY t.due_date ASC
	  ");
	  $st->execute([$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);
	  return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}
	
	public static function stats(): array {
	  $r1 = DB::pdo()->query("SELECT status, COUNT(*) cnt FROM tasks GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
	  $r2 = DB::pdo()->query("SELECT c.name, COUNT(*) cnt FROM tasks t JOIN customers c ON c.id=t.customer_id WHERE t.status='offen' GROUP BY c.id ORDER BY cnt DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) ?: [];
	  $r3 = DB::pdo()->query("SELECT DATE(due_date) d, COUNT(*) cnt FROM tasks WHERE due_date >= CURDATE() GROUP BY DATE(due_date) ORDER BY d ASC LIMIT 14")->fetchAll(PDO::FETCH_ASSOC) ?: [];
	  return ['byStatus'=>$r1, 'topCustomers'=>$r2, 'nextDays'=>$r3];
	}

public static function find(int $id): ?array {
  $st = DB::pdo()->prepare("SELECT * FROM tasks WHERE id = ? LIMIT 1");
  $st->execute([$id]);
  return $st->fetch() ?: null;
}

	public static function update(int $id, array $data): array {
	  $errors = [];
	  if (trim($data['title'] ?? '') === '') $errors[] = 'Titel ist Pflicht.';
	  if (!in_array($data['status'] ?? 'offen', ['offen','ausstehend','erledigt'], true)) $errors[] = 'Ungültiger Status.';
	  if (!empty($data['is_paused']) && empty($data['pause_reason'])) $errors[] = 'Bitte Pausierungsgrund angeben.';
	  if ($errors) return ['ok'=>false,'errors'=>$errors];

	  $st = DB::pdo()->prepare("
		UPDATE tasks SET
		  system_id=?, title=?, status=?, is_paused=?, pause_reason=?, is_recurring=?, due_date=?, comment=?
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
		$id
	  ]);
	  return ['ok'=>true];
	}

	public static function nextFromCustomer(array $c): ?\DateTimeImmutable {
	  date_default_timezone_set('Europe/Zurich');
	  $now   = new \DateTimeImmutable('now');

	  $type  = $c['maintenance_type'] ?? null;
	  if (!$type || $type==='none' || $type==='paused') return null;

	  // Falls Uhrzeit hinterlegt: hh:mm:ss -> für das Ziel verwenden
	  $time = $c['maintenance_time'] ?? null;
	  $hh = $mm = 0;
	  if ($time) { [$hh,$mm] = array_map('intval', explode(':', $time, 3)); }

	  switch ($type) {
		case 'daily': {
		  $today = (new \DateTimeImmutable('today'))->setTime($hh, $mm, 0);
		  return $now <= $today ? $today : $today->modify('+1 day');
		}
		case 'weekly':
		case 'biweekly': {
		  $wd     = max(1, min(7, (int)($c['maintenance_weekday'] ?? 1))); // 1=Mo..7=So
		  $todayW = (int)(new \DateTimeImmutable('now'))->format('N');
		  $base   = (new \DateTimeImmutable('today'))->setTime($hh,$mm,0);

		  $diff = $wd - $todayW; // -6..+6
		  $candidate = $base->modify(($diff >= 0 ? "+$diff days" : "$diff days"));
		  // wenn Termin heute, aber Uhrzeit schon vorbei → eine Woche vor
		  if ($candidate <= $now) $candidate = $candidate->modify('+1 week');

		  // alle 2 Wochen: wenn Kandidat in ungerader/gerader KW „nicht passt“, +1 weitere Woche
		  // simple Heuristik: wenn today->format('W') und candidate->format('W') gleiche Parität haben,
		  // dann +1 Woche (dadurch „jede zweite Woche“)
		  $parNow  = ((int)$now->format('W')) % 2;
		  $parCand = ((int)$candidate->format('W')) % 2;
		  if ($parNow === $parCand) {
			$candidate = $candidate->modify('+1 week');
		  }
		  return $candidate;
		}
		case 'monthly': {
		  // z.B. „1. Donnerstag“: maintenance_week_of_month in {1,2,3,4,-1}, weekday 1..7
		  $wom = (int)($c['maintenance_week_of_month'] ?? 1);
		  $wd  = max(1, min(7, (int)($c['maintenance_weekday'] ?? 1)));
		  $ref = new \DateTimeImmutable('first day of this month '.$hh.':'.$mm.':00');
		  $candidate = self::nthWeekdayOfMonth($ref, $wom, $wd);
		  if ($candidate <= $now) {
			$refNext = $ref->modify('first day of next month');
			$candidate = self::nthWeekdayOfMonth($refNext, $wom, $wd);
		  }
		  return $candidate;
		}
		case 'yearly': {
		  $m = (int)($c['maintenance_year_month'] ?? 0);
		  $d = (int)($c['maintenance_year_day']   ?? 0);
		  if ($m<1 || $d<1) return null;
		  $year = (int)date('Y');
		  $cand = (new \DateTimeImmutable("$year-$m-$d"))->setTime($hh,$mm,0);
		  if ($cand <= $now) $cand = $cand->modify('+1 year');
		  return $cand;
		}
	  }
	  return null;
	}

	public static function rollForward(\DateTimeImmutable $dt, string $type): \DateTimeImmutable {
	  switch ($type) {
		case 'daily':     return $dt->modify('+1 day');
		case 'weekly':    return $dt->modify('+1 week');
		case 'biweekly':  return $dt->modify('+2 weeks');
		case 'monthly':   return $dt->modify('+1 month');
		case 'yearly':    return $dt->modify('+1 year');
	  }
	  return $dt;
	}

	// Berechnung der nächsten Fälligkeit
	private static function nthWeekdayOfMonth(\DateTimeImmutable $firstOfMonth, int $nth, int $weekday): \DateTimeImmutable {
	  // $weekday: 1=Mo..7=So ; $nth: 1..4 oder -1=letzter
	  $month = (int)$firstOfMonth->format('m');
	  $year  = (int)$firstOfMonth->format('Y');

	  if ($nth === -1) {
		// letzter Wochentag im Monat
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
  $st = DB::pdo()->prepare("DELETE FROM tasks WHERE id = ? LIMIT 1");
  return $st->execute([$id]);
}

  /**
   * In Buckets gruppieren: überfällig, heute, morgen, diese Woche.
   * Zeitzone: Europe/Zurich
   */
	public static function groupByDueBuckets(array $tasks): array {
	  date_default_timezone_set('Europe/Zurich');
	  $today    = new \DateTimeImmutable('today');
	  $tomorrow = $today->modify('+1 day');
	  $weekday  = (int)$today->format('w'); // 0=So..6=Sa
	  $daysToSunday = (7 - $weekday) % 7;
	  $endOfWeek = $today->modify("+{$daysToSunday} days")->setTime(23,59,59);

	  $groups = ['overdue'=>[], 'today'=>[], 'tomorrow'=>[], 'thisweek'=>[]];

	  foreach ($tasks as $t) {
		// 1) harte Pause: Intervall greift nicht
		if (!empty($t['is_paused'])) {
		  // aber wenn die Aufgabe ein explizites due_date hat, zeigen wir sie trotzdem nach due_date
		  if (empty($t['due_date'])) continue;
		}

		// 2) effektive Fälligkeit bestimmen
		$eff = null;
		if (!empty($t['due_date'])) {
		  $eff = new \DateTimeImmutable($t['due_date']);
		} elseif (!empty($t['is_recurring'])) {
		  $eff = self::nextFromCustomer($t); // nutzt die mitgeladenen Customer-Felder
		  if (!$eff) continue;
		} else {
		  continue; // weder due_date noch Intervall
		}

		// 3) Bucket
		if ($eff < $today) {
		  $t['effective_due'] = $eff; $groups['overdue'][] = $t;
		} elseif ($eff < $today->modify('+1 day')) {
		  $t['effective_due'] = $eff; $groups['today'][] = $t;
		} elseif ($eff < $tomorrow->modify('+1 day')) {
		  $t['effective_due'] = $eff; $groups['tomorrow'][] = $t;
		} elseif ($eff <= $endOfWeek) {
		  $t['effective_due'] = $eff; $groups['thisweek'][] = $t;
		}
	  }

	  // optional: innerhalb der Buckets nach effective_due sortieren
	  foreach ($groups as &$g) {
		usort($g, function($a,$b){
		  $da = $a['effective_due']; $db = $b['effective_due'];
		  return $da <=> $db;
		});
	  }

	  return $groups;
	}


}
