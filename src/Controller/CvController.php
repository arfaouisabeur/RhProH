<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class CvController extends AbstractController
{
    #[Route('/cv/{filename}', name: 'app_cv_download', requirements: ['filename' => '.+'])]
    public function download(string $filename): Response
    {
        // Nettoyer le nom de fichier pour éviter les attaques de traversée de répertoire
        $filename = basename($filename);
        
        $cvDirectory = $this->getParameter('cv_directory');
        $filePath = $cvDirectory . '/' . $filename;
        
        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('Fichier CV non trouvé.');
        }
        
        return new BinaryFileResponse($filePath);
    }
}