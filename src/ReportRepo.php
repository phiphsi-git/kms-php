<?php
namespace App;

use PDO;
use DateTimeImmutable;

// --------------------------------------------------------------------------------------
// 1. TCPDF NOTFALL-SUCHE (Verhindert "Class not found")
// --------------------------------------------------------------------------------------
if (!class_exists('\TCPDF')) {
    $possiblePaths = [
		__DIR__ . '/../vendor/tcpdf_min/tcpdf.php',
        __DIR__ . '/../tcpdf.php',                  
        __DIR__ . '/../../tcpdf.php',               
        __DIR__ . '/../src/tcpdf.php',
        __DIR__ . '/../vendor/tcpdf/tcpdf.php',     
        __DIR__ . '/../lib/tcpdf/tcpdf.php'
    ];
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) { require_once $path; break; }
    }
}

// --------------------------------------------------------------------------------------
// 2. PDF KLASSE DEFINIEREN (Nur wenn TCPDF erfolgreich geladen wurde)
// --------------------------------------------------------------------------------------
/**
if (class_exists('\TCPDF')) {
    /**
     * Erweiterte PDF-Klasse für Kopf- und Fußzeilen (Bernauer AG Design)
     
    class ReportPDF extends \TCPDF {
        public $headerLogoPath = '';
        public $headerAddress  = '';

        // KOPFZEILE: Automatisch auf jeder Seite
        public function Header() {
            // 1. Logo der Bernauer AG oben rechts
            if (!empty($this->headerLogoPath) && file_exists($this->headerLogoPath)) {
                // X=150mm, Y=8mm, Breite=45mm
                $this->Image($this->headerLogoPath, 150, 8, 45, 0, '', '', 'T', false, 300, 'R', false, false, 0, false, false, false);
            }

            // 2. Adresse oben links
            $this->SetY(10); 
            $this->SetX(20); 
            $this->SetFont('helvetica', '', 9);
            $this->SetTextColor(100, 100, 100); // Dunkelgrau
            $this->Cell(0, 0, $this->headerAddress, 0, 1, 'L');
            
            // 3. Trennlinie
            $this->SetDrawColor(200, 200, 200);
            $this->Line(20, 25, 190, 25);
        }

        // FUSSZEILE: Seitenzahlen
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', '', 9);
            $this->SetTextColor(128, 128, 128);
            // "Seite X / Y" rechtsbündig
            $this->Cell(0, 10, 'Seite ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
        }
    }
}
*/

class ReportRepo {

    public static function listByCustomer(int $cid): array {
        $st = DB::pdo()->prepare("SELECT * FROM customer_reports WHERE customer_id=? ORDER BY created_at DESC");
        $st->execute([$cid]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getLastReportDate(int $customerId): ?string {
        $pdo = DB::pdo();
        try {
            $stmt = $pdo->prepare("SELECT created_at FROM customer_reports WHERE customer_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$customerId]);
            return $stmt->fetchColumn() ?: null;
        } catch (\Throwable $e) { return null; }
    }

    /**
     * Hauptfunktion zur Generierung des Reports
     */
    public static function generateCustomerReport(int $customerId, int $userId, DateTimeImmutable $reportDate, DateTimeImmutable $from, DateTimeImmutable $to, string $comment, array $files): array {
        // SICHERHEITSCHECK: TCPDF MUSS DA SEIN
        if (!class_exists('\TCPDF') || !class_exists('App\ReportPdf')) {
            return ['ok' => false, 'errors' => ['Systemfehler: Die TCPDF-Bibliothek wurde nicht gefunden. Bitte prüfen Sie, ob tcpdf.php im Hauptverzeichnis oder in src liegt.']];
        }

        $pdo = DB::pdo();
        
        // 1. Daten laden
        $cust = CustomerRepo::findWithDetails($customerId);
        if (!$cust) return ['ok'=>false, 'errors'=>['Kunde nicht gefunden']];

        $creator = UserRepo::findById($userId);
        $creatorName = $creator['name'] ?? $creator['email'] ?? 'System';
        
        $systems = SystemRepo::listByCustomer($customerId);

        // 2. Pfade vorbereiten
        $baseDir = rtrim(Config::storageDir(), '/');
        $reportDir = "$baseDir/customers/$customerId/reports";
        if (!is_dir($reportDir)) @mkdir($reportDir, 0775, true);

        // Pfad zum Bernauer AG Logo (im public/assets/img Ordner)
        // Wir gehen von src/ nach oben (..) dann public/assets/img/
        $companyLogo = __DIR__ . '/../public/assets/img/bernauerag.jpg';

        // 3. Anhänge verarbeiten (Bilder filtern für Seite 4)
        $imagesToEmbed = [];
        $otherAttachments = [];
        
        if (!empty($files['name'][0])) {
            $attDir = "$reportDir/attachments"; 
            if (!is_dir($attDir)) @mkdir($attDir, 0775, true);
            
            foreach ($files['name'] as $i => $name) {
                if (($files['error'][$i] ?? 1) === 0) {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', basename($name));
                    $targetPath = "$attDir/$safeName";
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                        // Ist es ein Bild?
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                            $imagesToEmbed[] = ['path' => $targetPath, 'name' => $name];
                        } else {
                            $otherAttachments[] = $name;
                        }
                    }
                }
            }
        }

        // 4. Aufgaben laden
        $sql = "SELECT t.*, s.name as system_name FROM tasks t LEFT JOIN systems s ON s.id = t.system_id
                WHERE t.customer_id = ? AND (
                    (t.created_at BETWEEN ? AND ?) OR 
                    (t.due_date BETWEEN ? AND ?) OR
                    (t.updated_at BETWEEN ? AND ?)
                )
                ORDER BY t.due_date ASC, t.id ASC";
        $stmt = $pdo->prepare($sql);
        $f = $from->format('Y-m-d 00:00:00'); $t = $to->format('Y-m-d 23:59:59');
        $stmt->execute([$customerId, $f, $t, $f, $t, $f, $t]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // --- PDF START ---
        try {
            $pdf = new ReportPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Header-Daten setzen
            $pdf->headerAddress = "Bernauer AG, Industriestr. 9, 8712 Stäfa";
            $pdf->headerLogoPath = $companyLogo;

            $pdf->SetCreator('KMS');
            $pdf->SetAuthor($creatorName);
            $pdf->SetTitle('Wartungsbericht - ' . $cust['name']);
            
            // Ränder: Oben 35mm Platz für Header
            $pdf->SetMargins(20, 35, 20);
            $pdf->SetHeaderMargin(10);
            $pdf->SetFooterMargin(15);
            $pdf->SetAutoPageBreak(TRUE, 25);

			// ==========================================
			// SEITE 1: DECKBLATT
			// ==========================================
			$pdf->AddPage();

			$pdf->SetY(40); 
			$pdf->SetFont('helvetica', 'B', 24);
			$pdf->SetTextColor(0, 0, 0);

			// 1. Titel "Wartungsbericht" links
			$pdf->Cell(100, 15, 'Wartungsbericht', 0, 0, 'L');

			// 2. Kundenlogo rechts auf gleicher Höhe wie der Titel (X=150, Y=38)
			if (!empty($cust['logo_url'])) {
				@$pdf->Image($cust['logo_url'], 150, 38, 40, 20, '', '', '', true, 300, 'R');
			}

			$pdf->Ln(25);

			// 3. Graue Box für Kundendaten (STRENG OHNE LOGO-LOGIK)
			$boxY = $pdf->GetY();
			$pdf->SetFillColor(245, 245, 245);
			$pdf->SetDrawColor(200, 200, 200);
			$pdf->Rect(20, $boxY, 170, 35, 'DF');

			// Textinhalt der Box
			$pdf->SetXY(25, $boxY + 6);
			$pdf->SetFont('helvetica', 'B', 14);
			$pdf->Cell(100, 8, $cust['name'], 0, 1);

			$pdf->SetFont('helvetica', '', 11);
			$pdf->SetX(25);
			$pdf->Cell(100, 6, ($cust['street'] ?? ''), 0, 1);
			$pdf->SetX(25);
			$pdf->Cell(100, 6, ($cust['zip'] ?? '') . ' ' . ($cust['city'] ?? ''), 0, 1);

			// Metadaten unter der Box
			$pdf->SetY($boxY + 45);
		
            // Unter der Box weiter
            $pdf->SetY($boxY + 55);

            // Metadaten Tabelle (Zeitraum, Ersteller)
            $pdf->SetFont('helvetica', '', 11);
            $htmlMeta = '
            <table cellpadding="6" cellspacing="0" border="0" style="width:100%;">
                <tr style="border-bottom:1px solid #ddd;">
                    <td width="35%"><strong>Berichtszeitraum:</strong></td>
                    <td width="65%">' . $from->format('d.m.Y') . ' - ' . $to->format('d.m.Y') . '</td>
                </tr>
                <tr>
                    <td><strong>Generiert am:</strong></td>
                    <td>' . $reportDate->format('d.m.Y H:i') . '</td>
                </tr>
                <tr>
                    <td><strong>Erstellt durch:</strong></td>
                    <td>' . htmlspecialchars($creatorName) . '</td>
                </tr>
                <tr>
                    <td><strong>Anzahl Aufgaben:</strong></td>
                    <td>' . count($tasks) . '</td>
                </tr>
            </table>';
            
            $pdf->writeHTML($htmlMeta, true, false, true, false, '');

            // Optional: Bemerkungstext auch aufs Deckblatt, wenn er kurz ist
            if (!empty($comment)) {
                $pdf->Ln(15);
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Write(0, 'Bemerkungen / Management Summary');
                $pdf->Ln(8);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->MultiCell(0, 8, $comment, 0, 'L');
            }

            // ==========================================
            // SEITE 2: SYSTEMÜBERSICHT
            // ==========================================
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Erfasste Systeme', 0, 1, 'L');
            $pdf->Line(20, 45, 190, 45); // Linie unter Titel
            $pdf->Ln(5);

            if (empty($systems)) {
                $pdf->SetFont('helvetica', 'I', 10);
                $pdf->Cell(0, 10, 'Keine Systeme für diesen Kunden hinterlegt.', 0, 1);
            } else {
                $htmlSys = '<table border="1" cellpadding="5" cellspacing="0" width="100%">
                    <tr style="background-color:#e3f2fd; font-weight:bold;">
                        <th width="40%">Systemname</th>
                        <th width="30%">Typ</th>
                        <th width="30%">IP / Host</th>
                    </tr>';
                
                foreach ($systems as $s) {
                    $htmlSys .= '<tr>
                        <td>' . htmlspecialchars($s['name']) . '</td>
                        <td>' . htmlspecialchars($s['type']) . '</td>
                        <td>' . htmlspecialchars($s['ip_address'] ?? '') . '</td>
                    </tr>';
                }
                $htmlSys .= '</table>';
                
                $pdf->SetFont('helvetica', '', 10);
                $pdf->writeHTML($htmlSys, true, false, true, false, '');
            }

            // ==========================================
            // SEITE 3ff: AUFGABEN & CHECKLISTEN
            // ==========================================
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Durchgeführte Aufgaben & Tätigkeiten', 0, 1, 'L');
            $pdf->Line(20, 45, 190, 45);
            $pdf->Ln(10);

            if (empty($tasks)) {
                $pdf->SetFont('helvetica', 'I', 10);
                $pdf->Cell(0, 10, 'Keine Aufgaben im gewählten Zeitraum bearbeitet.', 0, 1);
            } else {
                foreach ($tasks as $task) {
                    $statusMap = ['erledigt'=>'#28a745', 'offen'=>'#d9534f', 'ausstehend'=>'#ffc107'];
                    $stCol = $statusMap[$task['status']] ?? '#000000';
                    
                    $title = ($task['system_name'] ? '['.$task['system_name'].'] ' : '') . $task['title'];
                    
                    // HTML Block für die Aufgabe
                    $htmlTask = '<div style="background-color:#fafafa; border-bottom:1px solid #ccc; padding:5px;">';
                    // Header Zeile
                    $htmlTask .= '<table border="0" cellpadding="2" width="100%"><tr>';
                    $htmlTask .= '<td width="80%"><strong>' . htmlspecialchars($title) . '</strong></td>';
                    $htmlTask .= '<td width="20%" align="right" style="color:'.$stCol.'; font-weight:bold;">' . strtoupper($task['status']) . '</td>';
                    $htmlTask .= '</tr></table>';

                    // Kommentar
                    if (!empty($task['comment'])) {
                        $htmlTask .= '<div style="color:#555; font-size:9pt; font-style:italic; padding-left:5px;">Notiz: ' . nl2br(htmlspecialchars($task['comment'])) . '</div>';
                    }
                    
                    // Checkpoints laden
                    $cpStmt = $pdo->prepare("SELECT * FROM task_checkpoints WHERE task_id = ? ORDER BY sort_order ASC");
                    $cpStmt->execute([$task['id']]);
                    $cps = $cpStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($cps)) {
                        $htmlTask .= '<table cellpadding="2" cellspacing="0" border="0" style="font-size:9pt; color:#333;">';
                        foreach ($cps as $cp) {
                            $isDone = !empty($cp['is_done']);
                            // Grünes OK oder graue Klammern
                            $mark = $isDone ? '<span style="color:#28a745; font-weight:bold;">[OK]</span>' : '<span style="color:#999;">[ ]</span>';
                            $lbl = htmlspecialchars($cp['label']);
                            if (!empty($cp['comment'])) {
                                $lbl .= ' <span style="color:#777; font-style:italic;">(' . htmlspecialchars($cp['comment']) . ')</span>';
                            }
                            $htmlTask .= '<tr><td width="30">' . $mark . '</td><td>' . $lbl . '</td></tr>';
                        }
                        $htmlTask .= '</table>';
                    }
                    $htmlTask .= '</div><br><br>'; // Abstand

                    $pdf->SetFont('helvetica', '', 10);
                    $pdf->writeHTML($htmlTask, true, false, true, false, '');
                }
            }

            // ==========================================
            // SEITE 4: ANHÄNGE (BILDER)
            // ==========================================
            if (!empty($imagesToEmbed)) {
                $pdf->AddPage();
                $pdf->SetFont('helvetica', 'B', 16);
                $pdf->Cell(0, 10, 'Anhänge & Screenshots', 0, 1, 'L');
                $pdf->Line(20, 45, 190, 45);
                $pdf->Ln(10);

                foreach ($imagesToEmbed as $img) {
                    // Falls Seite voll ist, neue Seite
                    if ($pdf->GetY() > 240) { 
                        $pdf->AddPage(); 
                        $pdf->Ln(10); 
                    }

                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell(0, 8, 'Datei: ' . $img['name'], 0, 1);
                    
                    // Bild einbetten
                    // Parameter: File, X, Y, W, H, Type, Link, Align, Resize, Dpi, Align, ImgMask, ImgBorder, FitBox, Hidden, FitOnPage
                    $pdf->Image($img['path'], $pdf->GetX(), $pdf->GetY(), 170, 0, '', '', '', true, 300, '', false, false, 0, true, false, false);
                    
                    // Cursor unter das Bild schieben
                    $pdf->Ln(5); 
                }
            }

            // Liste der nicht-Bild Anhänge (z.B. ZIP, PDF)
            if (!empty($otherAttachments)) {
                 if (empty($imagesToEmbed)) $pdf->AddPage(); // Neue Seite falls nötig
                 
                 $pdf->Ln(10);
                 $pdf->SetFont('helvetica', 'B', 12);
                 $pdf->Write(0, 'Weitere Dateien (liegen im Ordner):');
                 $pdf->SetFont('helvetica', '', 10);
                 $htmlAtt = '<ul>';
                 foreach ($otherAttachments as $oa) {
                     $htmlAtt .= '<li>' . htmlspecialchars($oa) . '</li>';
                 }
                 $htmlAtt .= '</ul>';
                 $pdf->writeHTML($htmlAtt, true, false, true, false, '');
            }

            // --- SPEICHERN ---
            $filename = "Report_" . preg_replace('/[^a-zA-Z0-9]/', '_', $cust['name']) . "_" . date('Ymd_Hi') . ".pdf";
            $target = $reportDir . '/' . $filename;
            
            $pdf->Output($target, 'F'); // In Datei speichern

            // DB Eintrag
            $stmt = $pdo->prepare("INSERT INTO customer_reports (customer_id, created_by, title, filename, file_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$customerId, $userId, "Wartungsbericht " . $reportDate->format('d.m.Y'), $filename, $target]);
            
            if (class_exists('\App\ChangeLogRepo')) {
                \App\ChangeLogRepo::log($customerId, 'report', 'create', "PDF Report erstellt: $filename");
            }

            return ['ok'=>true];

        } catch (\Throwable $e) {
            return ['ok'=>false, 'errors'=>['PDF Fehler: ' . $e->getMessage()]];
        }
    }
    
    public static function delete(int $id): bool {
        $st = DB::pdo()->prepare("SELECT file_path FROM customer_reports WHERE id=?");
        $st->execute([$id]);
        $path = $st->fetchColumn();
        $ok = DB::pdo()->prepare("DELETE FROM customer_reports WHERE id=?")->execute([$id]);
        if ($ok && $path && file_exists($path)) @unlink($path);
        return $ok;
    }
}