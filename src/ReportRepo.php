<?php
declare(strict_types=1);

namespace App;

use PDO;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class ReportRepo
{
    private const TCPDF_REL = '/../vendor/tcpdf_min/tcpdf.php';

    private static function reportDir(int $customerId): string {
        $base = rtrim(Config::STORAGE_DIR, '/')."/customers/$customerId/reports";
        if (!is_dir($base)) @mkdir($base, 0775, true);
        return $base;
    }

    private static function columnExists(string $table, string $column): bool {
        $st = DB::pdo()->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $st->execute([$table, $column]);
        return (bool)$st->fetchColumn();
    }

    public static function listByCustomer(int $customerId): array {
        $st = DB::pdo()->prepare("
            SELECT id, customer_id, filename, file_path, created_at
            FROM customer_reports
            WHERE customer_id=?
            ORDER BY created_at DESC, id DESC
        ");
        $st->execute([$customerId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function delete(int $id): bool {
        $st = DB::pdo->prepare("SELECT customer_id, file_path FROM customer_reports WHERE id=? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) return false;

        $ok = DB::pdo()->prepare("DELETE FROM customer_reports WHERE id=? LIMIT 1")->execute([$id]);
        if ($ok && !empty($r['file_path']) && is_file($r['file_path'])) @unlink($r['file_path']);
        return $ok;
    }

    /**
     * Report erstellen – nur Änderungen seit letztem Report bis $to (CH-Zeit).
     * $to: Zielzeitpunkt (z. B. aus datetime-local). Standard = now(CH).
     */
    public static function generateCustomerReport(int $customerId, int $createdByUserId, ?DateTimeInterface $to = null): array
    {
        $tcpdfPath = __DIR__.self::TCPDF_REL;
        if (!is_file($tcpdfPath)) return ['ok'=>false, 'errors'=>['TCPDF nicht gefunden: '.$tcpdfPath]];
        require_once $tcpdfPath;

        $c = self::getCustomer($customerId);
        if (!$c) return ['ok'=>false,'errors'=>['Kunde nicht gefunden.']];
        $user = self::getUser($createdByUserId);

        $tz   = new DateTimeZone('Europe/Zurich');
        $to   = $to ? (new DateTimeImmutable($to->format('Y-m-d H:i:s'), $tz)) : new DateTimeImmutable('now', $tz);
        $from = self::getLastReportAt($customerId) ?? new DateTimeImmutable('1970-01-01 00:00:00', $tz);

        // Delta laden
        $changes = self::getTaskChangesSince($customerId, $from, $to);
        $bySystem = [];
        foreach ($changes as $row) {
            $sid = (int)($row['system_id'] ?? 0);
            $bySystem[$sid][] = $row;
        }
        $hasChanges = !empty($changes);

        // PDF
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('KMS');
        $pdf->SetAuthor('KMS');
        $pdf->SetTitle('Wartungsreport '.$c['name']);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('dejavusans', '', 10);

        $pdf->AddPage();
        $htmlCover = self::renderCoverHtml($c, $user, $to, $from);
        $pdf->writeHTML($htmlCover, true, false, true, false, '');

        $pdf->AddPage();
        $htmlBody  = self::renderDeltaHtml($c, $bySystem, $to, $from, $hasChanges);
        $pdf->writeHTML($htmlBody, true, false, true, false, '');

        $dir   = self::reportDir($customerId);
        $fname = self::safeFilename(sprintf('Report_%s_%s.pdf', self::slug($c['name']), $to->format('Ymd_His')));
        $path  = $dir.'/'.$fname;

        $pdf->Output($path, 'F');

        // Record anlegen
        $hasCreatedBy = self::columnExists('customer_reports','created_by');
        if ($hasCreatedBy) {
            DB::pdo()->prepare("INSERT INTO customer_reports (customer_id, filename, file_path, created_by) VALUES (?,?,?,?)")
                     ->execute([$customerId, $fname, $path, $createdByUserId ?: null]);
        } else {
            DB::pdo()->prepare("INSERT INTO customer_reports (customer_id, filename, file_path) VALUES (?,?,?)")
                     ->execute([$customerId, $fname, $path]);
        }

        return ['ok'=>true, 'filename'=>$fname, 'file_path'=>$path];
    }

    // ---------- Render ----------

    private static function renderCoverHtml(array $c, ?array $user, DateTimeImmutable $to, DateTimeImmutable $from): string
    {
        $logo   = trim($c['logo_url'] ?? '');
        $addr   = htmlspecialchars(trim(($c['street'] ?? '').', '.trim(($c['zip'] ?? '').' '.($c['city'] ?? ''))), ENT_QUOTES, 'UTF-8');
        $cust   = htmlspecialchars($c['name'] ?? 'Kunde', ENT_QUOTES, 'UTF-8');
        $byName = htmlspecialchars($user['name'] ?? ($user['email'] ?? '–'), ENT_QUOTES, 'UTF-8');
        $toStr  = $to->format('d.m.Y H:i');
        $fromStr= $from->format('d.m.Y H:i');

        $logoHtml = '';
        if ($logo !== '') {
            $safeLogo = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
            $logoHtml = '<div style="text-align:right"><img src="'.$safeLogo.'" height="48"></div>';
        }

        return <<<HTML
<style>
.h1{font-size:20px;margin:0 0 6mm 0}
.h2{font-size:14px;color:#444;margin:0 0 2mm 0}
.kv td{padding:2mm 3mm}
.divider{height:1px;background:#ddd;margin:6mm 0}
.box{border:1px solid #ddd;border-radius:6px;padding:4mm 5mm;margin-top:4mm}
</style>

$logoHtml
<h1 class="h1">Wartungsreport</h1>
<div class="h2">$cust</div>

<table class="kv" cellpadding="0" cellspacing="0" border="0">
  <tr><td><strong>Adresse</strong></td><td>$addr</td></tr>
  <tr><td><strong>Erstellt</strong></td><td>$toStr</td></tr>
  <tr><td><strong>Erstellt von</strong></td><td>$byName</td></tr>
  <tr><td><strong>Zeitraum</strong></td><td>seit $fromStr bis $toStr</td></tr>
</table>

<div class="divider"></div>
<div class="box">
  <strong>Hinweis:</strong> Dieser Report zeigt ausschließlich Aufgabenänderungen (Status/Notizen),
  die seit dem letzten Report bis zum angegebenen Zeitpunkt protokolliert wurden.
</div>
HTML;
    }

    private static function renderDeltaHtml(array $c, array $bySystem, DateTimeImmutable $to, DateTimeImmutable $from, bool $hasChanges): string
    {
        ksort($bySystem);
        if (!$hasChanges) {
            return '<p class="muted">Keine protokollierten Änderungen im Zeitraum.</p>';
        }

        $sysNames = self::systemNamesForCustomer((int)$c['id']);

        ob_start();
        echo <<<CSS
<style>
.h2{font-size:14px;color:#222;margin:0 0 3mm 0}
.muted{color:#666}
.badge{display:inline-block;border:1px solid #ccc;border-radius:10px;padding:1px 6px;font-size:10px;color:#333}
.sys{margin-bottom:6mm}
.task{margin:1mm 0 0 0}
.note{margin-left:4mm;color:#444}
.when{color:#666;font-size:10px;margin-left:2mm}
</style>
CSS;

        foreach ($bySystem as $sid => $rows) {
            $title = $sid ? ($sysNames[$sid] ?? ('System #'.$sid)) : 'Aufgaben ohne System';
            $titleHtml = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            echo '<div class="sys">';
            echo '<h2 class="h2">'.$titleHtml.'</h2>';

            foreach ($rows as $r) {
                $taskTitle = htmlspecialchars($r['task_title'] ?? ('Task #'.$r['task_id']), ENT_QUOTES, 'UTF-8');
                $status    = htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8');
                $when      = (new DateTimeImmutable($r['changed_at']))->setTimezone(new DateTimeZone('Europe/Zurich'))->format('d.m.Y H:i');
                $badge     = '<span class="badge">'.$status.'</span>';
                echo '<div class="task"><strong>'.$taskTitle.'</strong> '.$badge.' <span class="when">('.$when.')</span></div>';

                $cmt = trim((string)($r['comment'] ?? ''));
                if ($cmt !== '') echo '<div class="note">'.htmlspecialchars($cmt, ENT_QUOTES, 'UTF-8').'</div>';
            }
            echo '</div>';
        }

        return ob_get_clean();
    }

    // ---------- Data ----------

    private static function getCustomer(int $id): ?array {
        $st = DB::pdo()->prepare("SELECT id, name, street, zip, city, logo_url, website FROM customers WHERE id=? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    private static function getUser(int $id): ?array {
        if ($id <= 0) return null;
        $st = DB::pdo()->prepare("SELECT id, email AS name, email FROM users WHERE id=? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    private static function getLastReportAt(int $customerId): ?DateTimeImmutable {
        $st = DB::pdo()->prepare("SELECT MAX(created_at) FROM customer_reports WHERE customer_id=?");
        $st->execute([$customerId]);
        $val = $st->fetchColumn();
        if (!$val) return null;
        try {
            return new DateTimeImmutable((string)$val, new DateTimeZone('Europe/Zurich'));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function systemNamesForCustomer(int $customerId): array {
        $st = DB::pdo()->prepare("SELECT id, name FROM systems WHERE customer_id=?");
        $st->execute([$customerId]);
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $out[(int)$r['id']] = $r['name'];
        return $out;
    }

    /**
     * Letzter Statuswechsel je Aufgabe im Fenster (from, to].
     */
    private static function getTaskChangesSince(int $customerId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $sql = "
            SELECT
              tsl.task_id,
              tsl.status,
              tsl.comment,
              tsl.changed_by,
              tsl.changed_at,
              t.title     AS task_title,
              t.system_id AS system_id
            FROM task_status_log tsl
            INNER JOIN (
              SELECT task_id, MAX(changed_at) AS mx
              FROM task_status_log
              WHERE changed_at > :from AND changed_at <= :to
              GROUP BY task_id
            ) m ON m.task_id = tsl.task_id AND m.mx = tsl.changed_at
            INNER JOIN tasks t ON t.id = tsl.task_id
            WHERE t.customer_id = :cid
            ORDER BY t.system_id IS NULL, t.system_id, t.id
        ";
        $st = DB::pdo()->prepare($sql);
        $st->execute([
            ':from' => $from->format('Y-m-d H:i:s'),
            ':to'   => $to->format('Y-m-d H:i:s'),
            ':cid'  => $customerId,
        ]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // ---------- Helpers ----------

    private static function slug(string $s): string {
        $s = iconv('UTF-8','ASCII//TRANSLIT', $s);
        $s = preg_replace('~[^A-Za-z0-9]+~', '_', $s);
        $s = trim($s, '_');
        return $s !== '' ? $s : 'Report';
    }

    private static function safeFilename(string $name): string {
        return preg_replace('~[^\w\-.]+~', '_', $name);
    }
}
