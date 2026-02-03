<?php
namespace App;

require_once __DIR__ . '/../vendor/tcpdf_min/tcpdf.php';

class ReportPdf extends \TCPDF {

    public string $headerAddress = '';
    public string $headerLogoPath = '';

    public function Header() {
        // BAG Firmenlogo: Ganz weit oben rechts in die Ecke (X=0, Y=4), Breite=15mm
        if (!empty($this->headerLogoPath) && file_exists($this->headerLogoPath)) {
            $this->Image($this->headerLogoPath, -50, 4, 15, 0, '', '', 'T', false, 0, 'R');
        }

        $this->SetFont('dejavusans', '', 9);
        $this->SetTextColor(100, 100, 100);
        
        // Adresse: Links
        $this->SetXY(15, 10);
        $this->Cell(0, 10, $this->headerAddress, 0, 0, 'L');
        
        // Zierlinie
        $this->Line(15, 18, 195, 18, ['width'=>0.2, 'color'=>[200,200,200]]);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('dejavusans', '', 9);
        $this->SetTextColor(128, 128, 128);
        $pageStr = 'Seite ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
        $this->Cell(0, 10, $pageStr, 0, 0, 'R');
    }
}