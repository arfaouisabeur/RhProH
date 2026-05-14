<?php

namespace App\Controller;

use App\Entity\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QrCodeController extends AbstractController
{
    #[Route('/employe/qrcode', name: 'app_employe_qrcode')]
    public function employeQr(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $employe = $user->getEmploye();

        $data = json_encode([
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'matricule' => $employe?->getMatricule(),
            'poste' => $employe?->getPosition(),
        ], JSON_UNESCAPED_UNICODE);

        $builder = new Builder(
            writer: new PngWriter()
        );

        $result = $builder->build(
            data: $data,
            size: 400,
            margin: 20
        );

        return new Response(
            $result->getString(),
            200,
            [
                'Content-Type' => 'image/png'
            ]
        );
    }
}
