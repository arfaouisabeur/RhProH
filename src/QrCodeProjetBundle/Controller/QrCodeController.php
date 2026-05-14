<?php

namespace App\QrCodeProjetBundle\Controller;

use App\Entity\Projet;
use App\Repository\TacheRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class QrCodeController extends AbstractController
{
    private string $secret;
    private ?string $localIpOverride;

    public function __construct(
        #[Autowire('%kernel.secret%')] string $secret,
        #[Autowire('%env(LOCAL_IP_OVERRIDE)%')] ?string $localIpOverride = null
    ) {
        $this->secret = $secret;
        $this->localIpOverride = $localIpOverride;
    }

    #[Route('/projet/{id}/qr-code', name: 'app_projet_qr_code')]
    public function generateQrCode(Projet $projet, TacheRepository $tacheRepository): Response
    {
        $hash = hash_hmac('sha256', (string) $projet->getId(), $this->secret);

        $url = $this->generateUrl('public_projet_tasks', [
            'id' => $projet->getId(),
            'hash' => $hash,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // Détecter l'IP locale pour accès depuis téléphone
        $localIp = $this->detectLocalIp();
        
        // Remplacer localhost/127.0.0.1 par l'IP locale
        $qrUrl = preg_replace('#https?://[^/]+#', 'http://' . $localIp . ':8000', $url);
        $mobileUrl = $qrUrl;

        // URL pour le bouton "Tester le lien direct" sur le PC
        $targetUrl = $url;

        // Utilisation de QRServer API (Alternative fiable à Google Charts)
        $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrUrl);
        
        // On récupère l'image côté serveur et on l'encode en Base64.
        $qrBase64 = null;
        try {
            $context = stream_context_create([
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ],
            ]);
            $qrData = @file_get_contents($apiUrl, false, $context);
            if ($qrData) {
                $qrBase64 = 'data:image/png;base64,' . base64_encode($qrData);
            }
        } catch (\Exception $e) {
            // En cas d'échec total, l'URL directe sera utilisée en fallback dans le template
        }

        return $this->render('qr_code_projet/show.html.twig', [
            'projet'    => $projet,
            'apiUrl'    => $apiUrl,
            'qrBase64'  => $qrBase64,
            'targetUrl' => $targetUrl,
            'mobileUrl' => $mobileUrl,
            'localIp'   => $localIp,
        ]);
    }
    
    /**
     * Détecte l'IP locale de la machine (pour accès depuis téléphone sur même réseau)
     */
    private function detectLocalIp(): string
    {
        // Priorité 0: Utiliser l'override depuis .env si défini
        if (!empty($this->localIpOverride)) {
            return $this->localIpOverride;
        }
        
        // Méthode 1: Utiliser $_SERVER si disponible
        if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1' && $_SERVER['SERVER_ADDR'] !== '::1') {
            return $_SERVER['SERVER_ADDR'];
        }
        
        // Méthode 2: Utiliser gethostbyname (simple et fonctionne partout)
        try {
            $hostname = gethostname();
            if ($hostname) {
                $localIp = gethostbyname($hostname);
                if ($localIp && $localIp !== $hostname && $this->isValidLocalIp($localIp)) {
                    return $localIp;
                }
            }
        } catch (\Exception $e) {
            // Continue to next method
        }
        
        // Méthode 3: ipconfig/ifconfig selon l'OS
        $interfaces = '';
        if (PHP_OS_FAMILY === 'Windows') {
            $interfaces = @shell_exec('ipconfig') ?: '';
            // Chercher IPv4 Address
            if (preg_match_all('/IPv4[^:]*:\s*(\d+\.\d+\.\d+\.\d+)/i', $interfaces, $matches)) {
                foreach ($matches[1] as $ip) {
                    // Exclure loopback et IPs VirtualBox/VMware
                    if ($this->isValidLocalIp($ip)) {
                        return $ip;
                    }
                }
            }
        } else {
            // Linux/Mac
            $interfaces = @shell_exec('hostname -I') ?: @shell_exec('ifconfig') ?: '';
            if (preg_match_all('/(\d+\.\d+\.\d+\.\d+)/', $interfaces, $matches)) {
                foreach ($matches[1] as $ip) {
                    if ($this->isValidLocalIp($ip)) {
                        return $ip;
                    }
                }
            }
        }
        
        // Fallback: retourner 127.0.0.1 avec un avertissement
        return '127.0.0.1';
    }
    
    /**
     * Vérifie si une IP est valide pour l'accès local (pas loopback, pas VM)
     */
    private function isValidLocalIp(string $ip): bool
    {
        // Exclure loopback
        if (str_starts_with($ip, '127.')) {
            return false;
        }
        
        // Exclure IPs VirtualBox/VMware communes
        $excludedPrefixes = [
            '192.168.56.',   // VirtualBox Host-Only
            '192.168.152.',  // VirtualBox/VMware
            '192.168.178.',  // VMware
            '192.168.228.',  // VMware
            '192.168.233.',  // VMware
            '192.168.47.',   // VMware
            '169.254.',      // APIPA (auto-assigned)
        ];
        
        foreach ($excludedPrefixes as $prefix) {
            if (str_starts_with($ip, $prefix)) {
                return false;
            }
        }
        
        // Accepter les IPs privées valides
        return preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.)/', $ip) === 1;
    }

    #[Route('/public/projet/{id}/{hash}/tasks', name: 'public_projet_tasks')]
    public function publicTasks(Projet $projet, string $hash, TacheRepository $tacheRepository): Response
    {
        $expectedHash = hash_hmac('sha256', (string) $projet->getId(), $this->secret);

        if (!hash_equals($expectedHash, $hash)) {
            throw new AccessDeniedHttpException('Invalid token.');
        }

        $tasks = $tacheRepository->findBy(['projet' => $projet]);

        return $this->render('qr_code_projet/public_tasks.html.twig', [
            'projet' => $projet,
            'tasks' => $tasks,
        ]);
    }
}
