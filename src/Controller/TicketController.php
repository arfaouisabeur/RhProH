<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Repository\EventParticipationRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TicketController extends AbstractController
{
    #[Route('/employe/evenement/{id}/ticket', name: 'app_employe_ticket', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function generateTicket(
        Evenement $evenement,
        EventParticipationRepository $participationRepo
    ): Response {
        $user = $this->getUser();
        $employe = $user?->getEmploye();

        if (!$employe) {
            throw $this->createNotFoundException('Employé introuvable.');
        }

        $participation = $participationRepo->findOneBy([
            'evenement' => $evenement,
            'employe' => $employe,
            'statut' => 'accepte'
        ]);

        if (!$participation) {
            $this->addFlash('warning', 'Votre participation doit être validée pour obtenir un ticket.');
            return $this->redirectToRoute('app_employe_evenement_show', ['id' => $evenement->getId()]);
        }

        // 1. QR Code
        $qrContent = sprintf(
            "🎟️ TICKET OFFICIEL #%d\n" .
            "👤 Participant: %s\n" .
            "📧 Email: %s\n" .
            "📞 Tel: %s\n" .
            "📅 Evénement: %s\n" .
            "✅ Statut: VALIDE",
            $participation->getId(),
            strtoupper($user->getFullName()),
            $user->getEmail(),
            $user->getTelephone() ?? 'N/A',
            strtoupper($evenement->getTitre())
        );
        $writer = new SvgWriter();
        
        $qrCode = new QrCode(
            data: $qrContent,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(102, 29, 191), // #661dbf
            backgroundColor: new Color(255, 255, 255)
        );
        $qrCodeBase64 = $writer->write($qrCode)->getDataUri();

        // 1.5 Format Date in French
        $dateStr = $evenement->getDateDebut();
        $formattedDate = $dateStr;
        try {
            $dateObj = new \DateTime($dateStr);
            $formatter = new \IntlDateFormatter(
                'fr_FR',
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::NONE,
                null,
                \IntlDateFormatter::GREGORIAN,
                'EEEE d MMMM yyyy'
            );
            $formattedDate = ucwords($formatter->format($dateObj));
        } catch (\Exception $e) {
            // Fallback to original if intl or date fails
        }

        // 2. Image logic refinement (handle both local and remote URLs + WebP Conversion)
        $eventImageBase64 = null;
        $imageUrl = $evenement->getImageUrl();
        
        if ($imageUrl) {
            $data = null;
            $type = null;

            if (str_starts_with($imageUrl, 'http')) {
                // Remote URL
                try {
                    $context = stream_context_create([
                        "ssl" => ["verify_peer" => false, "verify_peer_name" => false],
                        "http" => ["header" => "User-Agent: PHP\r\n"]
                    ]);
                    $data = @file_get_contents($imageUrl, false, $context);
                    if ($data) {
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $type = $finfo->buffer($data);
                    }
                } catch (\Exception $e) {}
            } else {
                // Local Path
                $imageUrl = ltrim($imageUrl, '/');
                $fullPath = $this->getParameter('kernel.project_dir') . '/public/' . $imageUrl;
                $path = realpath($fullPath);
                
                if ($path && file_exists($path)) {
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $data = file_get_contents($path);
                    $type = ($ext === 'webp') ? 'image/webp' : ('image/' . $ext);
                }
            }

            // --- WebP to JPEG Conversion for Dompdf Compatibility ---
            if ($data && $type === 'image/webp') {
                if (function_exists('imagecreatefromwebp')) {
                    try {
                        $img = imagecreatefromstring($data);
                        if ($img) {
                            ob_start();
                            imagejpeg($img, null, 90);
                            $data = ob_get_clean();
                            $type = 'image/jpeg';
                            imagedestroy($img);
                        }
                    } catch (\Exception $e) {
                        $data = null; // Conversion failed, drop image to avoid crash
                        $type = null;
                    }
                } else {
                    // GD WebP support missing, must drop image to avoid Dompdf fatal error
                    $data = null;
                    $type = null;
                }
            }

            if ($data && $type) {
                $eventImageBase64 = 'data:' . $type . ';base64,' . base64_encode($data);
            }
        }

        // 3. PDF
        $html = $this->renderView('ticket/pdf.html.twig', [
            'evenement' => $evenement,
            'employe' => $employe,
            'participation' => $participation,
            'qrCode' => $qrCodeBase64,
            'eventImage' => $eventImageBase64,
            'formattedDate' => $formattedDate,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="ticket_' . $evenement->getId() . '.pdf"'
        ]);
    }
}
