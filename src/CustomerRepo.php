<?php
namespace App;

class CustomerRepo {

  /**
   * Kundenliste mit Kennzahlen (Suche & Sortierung).
   * $q    : Suche über Kundenname & Techniker-Mail
   * $sort : name_asc|name_desc|next_due_asc|next_due_desc|systems_desc
   */
  public static function listWithStats(string $q = '', string $sort = 'name_asc'): array {
    $pdo = DB::pdo();

    $where  = '';
    $params = [];
    if ($q !== '') {
      // zwei Platzhalter → HY093 vermeiden
      $where = "WHERE (c.name LIKE :q1 OR rt.email LIKE :q2)";
      $params[':q1'] = '%'.$q.'%';
      $params[':q2'] = '%'.$q.'%';
    }

    $order = match ($sort) {
      'name_desc'     => 'c.name DESC',
      'next_due_asc'  => 'next_due ASC',
      'next_due_desc' => 'next_due DESC',
      'systems_desc'  => 'systems_count DESC, c.name ASC',
      default         => 'c.name ASC',
    };

    $sql = "
      SELECT
        c.id, c.name, c.logo_url, c.website,
        rt.email AS technician_email,
        MIN(CASE WHEN t.status IN ('offen','ausstehend') THEN t.due_date END) AS next_due,
        COUNT(DISTINCT s.id) AS systems_count,
        SUM(CASE WHEN t.status='offen' THEN 1 ELSE 0 END)      AS tasks_open,
        SUM(CASE WHEN t.status='erledigt' THEN 1 ELSE 0 END)   AS tasks_done,
        SUM(CASE WHEN t.status='ausstehend' THEN 1 ELSE 0 END) AS tasks_pending
      FROM customers c
      LEFT JOIN users   rt ON rt.id = c.responsible_technician_id
      LEFT JOIN systems s  ON s.customer_id = c.id
      LEFT JOIN tasks   t  ON t.customer_id = c.id
      $where
      GROUP BY c.id
      ORDER BY $order
      LIMIT 500
    ";

    $st = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $st->bindValue($k, $v);
    $st->execute();
    return $st->fetchAll() ?: [];
  }

  /**
   * Kunden anlegen (inkl. Kontakte).
   * Intervall: none|daily|weekly|biweekly|monthly|yearly|paused (+ maintenance_pause_reason)
   */
	public static function create(array $data, array $contacts): array {
	  self::normalizeCustomerData($data);

	  $errors = self::validateMaintenance($data);
	  if ($data['name'] === '') $errors[] = 'Kundenname ist Pflicht.';
	  if ($data['website'] && !filter_var($data['website'], FILTER_VALIDATE_URL)) $errors[] = 'Webseite ist keine gültige URL.';
	  if ($data['logo_url'] && !filter_var($data['logo_url'], FILTER_VALIDATE_URL)) $errors[] = 'Logo-URL ist keine gültige URL.';
	  if ($errors) return ['ok'=>false,'errors'=>$errors];

	  // Spalten & Werte (Anzahl muss passen!)
	  $cols = [
		'name','street','zip','city','website','logo_url',
		'maintenance_type','maintenance_time','maintenance_weekday','maintenance_week_of_month','maintenance_year_month','maintenance_year_day',
		'maintenance_pause_reason',
		'responsible_technician_id','owner_user_id'
	  ];
	  $vals = [
		$data['name'], $data['street'], $data['zip'], $data['city'],
		$data['website'], $data['logo_url'],
		$data['maintenance_type'], $data['maintenance_time'],
		$data['maintenance_weekday'], $data['maintenance_week_of_month'],
		$data['maintenance_year_month'], $data['maintenance_year_day'],
		$data['maintenance_pause_reason'],
		$data['tech_id'], $data['owner_id']
	  ];

	  $placeholders = implode(',', array_fill(0, count($cols), '?'));
	  $sql = "INSERT INTO customers (".implode(',', $cols).") VALUES ($placeholders)";

	  $pdo = DB::pdo();
	  $pdo->beginTransaction();
	  try {
		// Kunde anlegen
		$st = $pdo->prepare($sql);
		$st->execute($vals);
		$customerId = (int)$pdo->lastInsertId();

		// Kontakte (leere Zeilen ignorieren)
		if (is_array($contacts) && $contacts) {
		  $cst = $pdo->prepare("
			INSERT INTO customer_contacts
			  (customer_id, name, phone, email, tech_questions, admin_questions, budget_approvals, credential_changes, ticket_creation, general_inquiries)
			VALUES (?,?,?,?,?,?,?,?,?,?)
		  ");
		  foreach ($contacts as $c) {
			$name  = trim($c['name']  ?? '');
			$phone = trim($c['phone'] ?? '');
			$email = trim($c['email'] ?? '');
			if ($name === '' && $email === '' && $phone === '') continue;

			$cst->execute([
			  $customerId,
			  $name !== '' ? $name : 'Kontakt',
			  $phone !== '' ? $phone : null,
			  $email !== '' ? $email : null,
			  !empty($c['tech_questions'])     ? 1 : 0,
			  !empty($c['admin_questions'])    ? 1 : 0,
			  !empty($c['budget_approvals'])   ? 1 : 0,
			  !empty($c['credential_changes']) ? 1 : 0,
			  !empty($c['ticket_creation'])    ? 1 : 0,
			  !empty($c['general_inquiries'])  ? 1 : 0,
			]);
		  }
		}

		$pdo->commit();

		// FS-Ordner erst NACH erfolgreichem Commit anlegen
		self::ensureStorage($customerId);

		return ['ok'=>true,'id'=>$customerId];

	  } catch (\PDOException $e) {
		$pdo->rollBack();
		error_log('CustomerRepo::create PDO: '.json_encode($e->errorInfo));
		return ['ok'=>false,'errors'=>['Fehler beim Speichern: '.$e->getMessage()]];
	  } catch (\Throwable $e) {
		$pdo->rollBack();
		error_log('CustomerRepo::create FATAL: '.$e->getMessage());
		return ['ok'=>false,'errors'=>['Fehler beim Speichern: '.$e->getMessage()]];
	  }
	}


  /** Details inkl. Kontakte & Kennzahlen */
  public static function findWithDetails(int $id): ?array {
    $pdo = DB::pdo();

    $st = $pdo->prepare("
      SELECT c.*,
             rt.email AS technician_email,
             ow.email AS owner_email
      FROM customers c
      LEFT JOIN users rt ON rt.id = c.responsible_technician_id
      LEFT JOIN users ow ON ow.id = c.owner_user_id
      WHERE c.id = ?
      LIMIT 1
    ");
    $st->execute([$id]);
    $c = $st->fetch();
    if (!$c) return null;

    $k = $pdo->prepare("SELECT * FROM customer_contacts WHERE customer_id = ? ORDER BY id ASC");
    $k->execute([$id]);
    $c['contacts'] = $k->fetchAll() ?: [];

    $s = $pdo->prepare("SELECT COUNT(*) FROM systems WHERE customer_id = ?");
    $s->execute([$id]);
    $c['systems_count'] = (int)$s->fetchColumn();

    $t = $pdo->prepare("
      SELECT
        SUM(CASE WHEN status='offen' THEN 1 ELSE 0 END)      AS open_cnt,
        SUM(CASE WHEN status='erledigt' THEN 1 ELSE 0 END)   AS done_cnt,
        SUM(CASE WHEN status='ausstehend' THEN 1 ELSE 0 END) AS pending_cnt
      FROM tasks WHERE customer_id = ?
    ");
    $t->execute([$id]);
    $c['tasks'] = $t->fetch() ?: ['open_cnt'=>0,'done_cnt'=>0,'pending_cnt'=>0];

    return $c;
  }

  /** Kunden aktualisieren (Kontakte neu schreiben) */
  public static function update(int $id, array $data, array $contacts): array {
    self::normalizeCustomerData($data);

    $errors = self::validateMaintenance($data);
    if (trim($data['name'] ?? '') === '') $errors[] = 'Kundenname ist Pflicht.';
    if ($data['website'] && !filter_var($data['website'], FILTER_VALIDATE_URL)) $errors[] = 'Webseite ist keine gültige URL.';
    if ($data['logo_url'] && !filter_var($data['logo_url'], FILTER_VALIDATE_URL)) $errors[] = 'Logo-URL ist keine gültige URL.';
    if ($errors) return ['ok'=>false,'errors'=>$errors];

	// Kundenordner nachträglich erfassen, falls nicht vorhanden
	self::ensureStorage($id);

    $pdo = DB::pdo();
    $pdo->beginTransaction();
    try {
      $u = $pdo->prepare("
        UPDATE customers SET
          name=?, street=?, zip=?, city=?, website=?, logo_url=?,
          maintenance_type=?, maintenance_time=?, maintenance_weekday=?, maintenance_week_of_month=?, maintenance_year_month=?, maintenance_year_day=?,
          maintenance_pause_reason=?,
          responsible_technician_id=?, owner_user_id=?
        WHERE id=? LIMIT 1
      ");
      $u->execute([
        $data['name'], $data['street'], $data['zip'], $data['city'],
        $data['website'] ?: null, $data['logo_url'] ?: null,
        $data['maintenance_type'] ?: null,
        $data['maintenance_time'] ?: null,
        $data['maintenance_weekday'],
        $data['maintenance_week_of_month'],
        $data['maintenance_year_month'],
        $data['maintenance_year_day'],
        $data['maintenance_pause_reason'] ?: null,
        $data['tech_id'], $data['owner_id'],
        $id
      ]);

      // Kontakte einfach neu schreiben
      $pdo->prepare("DELETE FROM customer_contacts WHERE customer_id = ?")->execute([$id]);
      if (is_array($contacts) && $contacts) {
        $cst = $pdo->prepare("
          INSERT INTO customer_contacts
            (customer_id, name, phone, email, tech_questions, admin_questions, budget_approvals, credential_changes, ticket_creation, general_inquiries)
          VALUES (?,?,?,?,?,?,?,?,?,?)
        ");
        foreach ($contacts as $c) {
          $name  = trim($c['name']  ?? '');
          $phone = trim($c['phone'] ?? '');
          $email = trim($c['email'] ?? '');
          if ($name === '' && $email === '' && $phone === '') continue;

          $cst->execute([
            $id,
            $name !== '' ? $name : 'Kontakt',
            $phone !== '' ? $phone : null,
            $email !== '' ? $email : null,
            !empty($c['tech_questions'])     ? 1 : 0,
            !empty($c['admin_questions'])    ? 1 : 0,
            !empty($c['budget_approvals'])   ? 1 : 0,
            !empty($c['credential_changes']) ? 1 : 0,
            !empty($c['ticket_creation'])    ? 1 : 0,
            !empty($c['general_inquiries'])  ? 1 : 0,
          ]);
        }
      }

      $pdo->commit();
      return ['ok'=>true];

    } catch (\Throwable $e) {
      $pdo->rollBack();
      error_log('CustomerRepo::update FATAL: '.$e->getMessage());
      return ['ok'=>false,'errors'=>['Fehler beim Aktualisieren: '.$e->getMessage()]];
    }
  }

  /* ---------------- Helper ---------------- */

  /** Normalisieren (Trim/Nulls) */
  private static function normalizeCustomerData(array &$data): void {
    $data['name']     = trim($data['name']     ?? '');
    $data['street']   = trim($data['street']   ?? '');
    $data['zip']      = trim($data['zip']      ?? '');
    $data['city']     = trim($data['city']     ?? '');
    $data['website']  = ($data['website']  ?? '') ?: null;
    $data['logo_url'] = ($data['logo_url'] ?? '') ?: null;

    $data['maintenance_type']          = ($data['maintenance_type']          ?? '') ?: null;
    $data['maintenance_time']          = ($data['maintenance_time']          ?? '') ?: null;
    $data['maintenance_weekday']       = ($data['maintenance_weekday']       ?? null) ?: null;
    $data['maintenance_week_of_month'] = ($data['maintenance_week_of_month'] ?? null) ?: null;
    $data['maintenance_year_month']    = ($data['maintenance_year_month']    ?? null) ?: null;
    $data['maintenance_year_day']      = ($data['maintenance_year_day']      ?? null) ?: null;
    $data['maintenance_pause_reason']  = trim($data['maintenance_pause_reason'] ?? '') ?: null;

    $data['tech_id']  = ($data['tech_id']  ?? null) ?: null;
    $data['owner_id'] = ($data['owner_id'] ?? null) ?: null;
  }

	// Kundenordner erfassen
	public static function ensureStorage(int $customerId): void {
	  $base = rtrim(\App\Config::STORAGE_DIR, '/')."/customers/$customerId";
	  @mkdir($base, 0775, true);
	  @mkdir("$base/files", 0775, true);
	  @mkdir("$base/reports", 0775, true);
	}

  /** Intervall-Validierung & Neutralisierung nicht benötigter Felder */
  private static function validateMaintenance(array &$data): array {
    $errors = [];
    switch ($data['maintenance_type']) {
      case 'none':
        $data['maintenance_time'] = $data['maintenance_weekday'] =
        $data['maintenance_week_of_month'] = $data['maintenance_year_month'] =
        $data['maintenance_year_day'] = null;
        $data['maintenance_pause_reason'] = null;
        break;

      case 'paused':
        if (empty($data['maintenance_pause_reason'])) {
          $errors[] = 'Bitte einen Grund für „pausiert“ angeben.';
        }
        $data['maintenance_time'] = $data['maintenance_weekday'] =
        $data['maintenance_week_of_month'] = $data['maintenance_year_month'] =
        $data['maintenance_year_day'] = null;
        break;

      case 'daily':
        if (empty($data['maintenance_time'])) $errors[] = 'Uhrzeit für täglich fehlt.';
        $data['maintenance_weekday'] = $data['maintenance_week_of_month'] =
        $data['maintenance_year_month'] = $data['maintenance_year_day'] = null;
        $data['maintenance_pause_reason'] = null;
        break;

      case 'weekly':
      case 'biweekly':
        if (empty($data['maintenance_time']))    $errors[] = 'Uhrzeit fehlt.';
        if (empty($data['maintenance_weekday'])) $errors[] = 'Wochentag fehlt.';
        $data['maintenance_week_of_month'] = $data['maintenance_year_month'] = $data['maintenance_year_day'] = null;
        $data['maintenance_pause_reason'] = null;
        break;

      case 'monthly':
        if (empty($data['maintenance_time']))          $errors[] = 'Uhrzeit fehlt.';
        if (empty($data['maintenance_weekday']))       $errors[] = 'Wochentag fehlt.';
        if (empty($data['maintenance_week_of_month'])) $errors[] = 'Woche im Monat fehlt.';
        $data['maintenance_year_month'] = $data['maintenance_year_day'] = null;
        $data['maintenance_pause_reason'] = null;
        break;

      case 'yearly':
        if (empty($data['maintenance_year_month']) || empty($data['maintenance_year_day'])) {
          $errors[] = 'Datum (Monat/Tag) für jährlich fehlt.';
        }
        $data['maintenance_time'] = $data['maintenance_weekday'] = $data['maintenance_week_of_month'] = null;
        $data['maintenance_pause_reason'] = null;
        break;

      case null:
      case '':
        $data['maintenance_time'] = $data['maintenance_weekday'] =
        $data['maintenance_week_of_month'] = $data['maintenance_year_month'] =
        $data['maintenance_year_day'] = null;
        $data['maintenance_pause_reason'] = null;
        break;

      default:
        $errors[] = 'Ungültiges Wartungsintervall.';
    }
    return $errors;
  }
  
	  public static function delete(int $id): bool {
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		try {
		  // DB: Kunden löschen (FKs: systems/tasks/files/reports cascaden)
		  $st = $pdo->prepare("DELETE FROM customers WHERE id=? LIMIT 1");
		  $st->execute([$id]);

		  $pdo->commit();

		  // FS: Kundenordner entfernen (nach Commit)
		  self::removeStorage($id);

		  return true;
		} catch (\Throwable $e) {
		  $pdo->rollBack();
		  error_log('Customer delete failed: '.$e->getMessage());
		  return false;
		}
	  }


	  private static function removeStorage(int $customerId): void {
		$dir = rtrim(Config::STORAGE_DIR,'/')."/customers/$customerId";
		if (!is_dir($dir)) return;
		$it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
		$ri = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($ri as $file) {
		  $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
		}
		@rmdir($dir);
	  }
	

}
