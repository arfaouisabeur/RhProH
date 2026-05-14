<?php

namespace App\Service;

use App\Entity\Candidature;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

class CertificatService
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function generateCertificat(Candidature $candidature, array $blockchainData): string
    {
        $user = $candidature->getCandidat()->getUser();
        $offre = $candidature->getOffreEmploi();

        $hash = (string) ($blockchainData['hash'] ?? '');
        $blockNumber = (string) ($blockchainData['block_number'] ?? '—');

        $nomCandidat = htmlspecialchars(trim(($user->getNom() ?? '') . ' ' . ($user->getPrenom() ?? '')));
        $titreOffre = htmlspecialchars($offre->getTitre() ?? 'Non précisé');
        $lieu = htmlspecialchars($offre->getLocalisation() ?? 'Non précisé');

        $dateCandidature = $candidature->getDateCandidature();
        $dateComplete = $dateCandidature
            ? $dateCandidature->format('d/m/Y à H:i')
            : date('d/m/Y à H:i');

        $dateSimple = $dateCandidature
            ? $dateCandidature->format('d/m/Y')
            : date('d/m/Y');

        $verifyUrl = 'http://127.0.0.1:8000/candidature/verify/' . $hash;
        $qrSrc = $this->generateQrCode($verifyUrl);

        $html = $this->buildHtml(
            $nomCandidat,
            $titreOffre,
            $lieu,
            $dateComplete,
            $dateSimple,
            htmlspecialchars($hash),
            htmlspecialchars($blockNumber),
            $qrSrc
        );

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $directory = $this->projectDir . '/public/certificats/';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = 'certificat_' . ($hash ?: uniqid()) . '.pdf';
        file_put_contents($directory . $filename, $dompdf->output());

        return $filename;
    }

    private function generateQrCode(string $verifyUrl): string
    {
        try {
            $qrCode = new QrCode(
                data: $verifyUrl,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 170,
                margin: 6,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(44, 62, 80),
                backgroundColor: new Color(255, 255, 255)
            );

            $writer = new PngWriter();
            return 'data:image/png;base64,' . base64_encode($writer->write($qrCode)->getString());
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function buildHtml(
        string $nomCandidat,
        string $titreOffre,
        string $lieu,
        string $dateComplete,
        string $dateSimple,
        string $hash,
        string $blockNumber,
        string $qrSrc
    ): string {
        $qrHtml = $qrSrc
            ? '<img src="' . $qrSrc . '" alt="QR Code" style="width:110px;height:110px;">'
            : '<div style="width:110px;height:110px;border:1px solid #ccc;"></div>';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Certificat de candidature</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #1f2937;
            background: #ffffff;
            font-size: 12px;
        }

        .page {
            width: 100%;
            padding: 28px;
        }

        .certificate {
            border: 2px solid #d1d5db;
            padding: 28px 30px;
            min-height: 760px;
            position: relative;
        }

        .top-border {
            border-top: 8px solid #1e3a8a;
            margin: -28px -30px 24px -30px;
        }

        .header {
            text-align: center;
            margin-bottom: 24px;
        }

        .title {
            font-size: 28px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }

        .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 14px;
        }

        .badge {
            display: inline-block;
            padding: 6px 14px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .intro {
            text-align: center;
            margin: 26px 0 10px;
            font-size: 14px;
            color: #4b5563;
        }

        .candidate-name {
            text-align: center;
            font-size: 26px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 26px;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }

        .main-table td {
            vertical-align: top;
        }

        .left-column {
            width: 70%;
            padding-right: 18px;
        }

        .right-column {
            width: 30%;
            text-align: center;
        }

        .info-box {
            border: 1px solid #d1d5db;
            background: #f9fafb;
            padding: 16px;
            border-radius: 8px;
        }

        .info-row {
            margin-bottom: 12px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .value {
            font-size: 13px;
            color: #111827;
            font-weight: 600;
        }

        .status-ok {
            color: #15803d;
            font-weight: bold;
        }

        .qr-box {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 14px 10px;
            background: #ffffff;
        }

        .qr-title {
            font-size: 11px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 10px;
        }

        .qr-subtitle {
            font-size: 10px;
            color: #6b7280;
            margin-top: 8px;
            line-height: 1.4;
        }

        .hash-box {
            margin-top: 14px;
            border: 1px solid #e5e7eb;
            background: #f3f4f6;
            border-radius: 8px;
            padding: 14px;
        }

        .hash-label {
            font-size: 10px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .hash-value {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 10px;
            color: #111827;
            word-wrap: break-word;
            line-height: 1.5;
        }

        .bottom-table {
            width: 100%;
            margin-top: 28px;
            border-collapse: collapse;
        }

        .bottom-table td {
            width: 50%;
            vertical-align: top;
        }

        .date-place {
            font-size: 12px;
            color: #374151;
            margin-bottom: 50px;
        }

        .signature-block {
            text-align: center;
            padding-top: 10px;
        }

        .signature-line {
            width: 220px;
            border-top: 1px solid #6b7280;
            margin: 0 auto 8px auto;
        }

        .signature-name {
            font-size: 12px;
            font-weight: bold;
            color: #111827;
        }

        .signature-role {
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
        }

        .footer {
            position: absolute;
            left: 30px;
            right: 30px;
            bottom: 22px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="certificate">
            <div class="top-border"></div>

            <div class="header">
                <div class="title">CERTIFICAT DE CANDIDATURE</div>
                <div class="subtitle">Attestation officielle de dépôt et de certification blockchain</div>
                <div class="badge">Blockchain certifiée • ETH Sepolia • Bloc #{$blockNumber}</div>
            </div>

            <div class="intro">Ce certificat atteste que la candidature suivante a été enregistrée avec succès au nom de :</div>
            <div class="candidate-name">{$nomCandidat}</div>

            <table class="main-table">
                <tr>
                    <td class="left-column">
                        <div class="info-box">
                            <div class="info-row">
                                <div class="label">Candidat</div>
                                <div class="value">{$nomCandidat}</div>
                            </div>

                            <div class="info-row">
                                <div class="label">Poste visé</div>
                                <div class="value">{$titreOffre}</div>
                            </div>

                            <div class="info-row">
                                <div class="label">Localisation</div>
                                <div class="value">{$lieu}</div>
                            </div>

                            <div class="info-row">
                                <div class="label">Date de dépôt</div>
                                <div class="value">{$dateComplete}</div>
                            </div>

                            <div class="info-row">
                                <div class="label">Statut</div>
                                <div class="value status-ok">✓ Candidature certifiée et enregistrée</div>
                            </div>
                        </div>

                        <div class="hash-box">
                            <div class="hash-label">Empreinte cryptographique SHA-256</div>
                            <div class="hash-value">{$hash}</div>
                        </div>
                    </td>

                    <td class="right-column">
                        <div class="qr-box">
                            <div class="qr-title">Vérification</div>
                            {$qrHtml}
                            <div class="qr-subtitle">
                                Scanner ce code pour vérifier l’authenticité du certificat.
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="bottom-table">
                <tr>
                    <td>
                        <div class="date-place">
                            Fait à : <strong>{$lieu}</strong><br>
                            Le : <strong>{$dateSimple}</strong>
                        </div>
                    </td>
                    <td>
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <div class="signature-name">Responsable RH</div>
                            <div class="signature-role">RH Pro Platform</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="footer">
                Certificat généré automatiquement par RH Pro.<br>
                Toute modification du contenu rend cette certification invalide.
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
