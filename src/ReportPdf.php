<?php
namespace App;

require_once __DIR__ . '/../vendor/tcpdf_min/tcpdf.php';

class ReportPdf extends \TCPDF {

    // KOPFZEILE (Links: Bernauer AG...)
    public function Header() {
        $this->SetFont('dejavusans', 'B', 10);
        $this->SetTextColor(100, 100, 100);
        // Position: 15mm von links, 10mm von oben
        $this->SetXY(15, 10);
        $this->Cell(0, 10, 'Bernauer AG, Industriestrasse 9, 8712 Stäfa', 0, 0, 'L');
        
        // Zierlinie
        $this->Line(15, 18, 195, 18, ['width'=>0.2, 'color'=>[200,200,200]]);
    }

    // FUSSZEILE (Rechts: Seitenzahl)
    public function Footer() {
        $this->SetY(-15); // 15mm von unten
        $this->SetFont('dejavusans', '', 9);
        $this->SetTextColor(128, 128, 128);
        
        // Seitenzahl rechtsbündig
        $pageStr = 'Seite ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
        $this->Cell(0, 10, $pageStr, 0, 0, 'R');
    }
}