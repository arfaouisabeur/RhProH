<?php
namespace App\Controller;

use App\Service\CaptchaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;  // ✅ Attribute et non Annotation

class CaptchaController extends AbstractController
{
    #[Route('/captcha/image', name: 'app_captcha_image')]
    public function image(CaptchaService $captchaService): Response
    {
        $code = $captchaService->generate();
        $width  = 160;
        $height = 50;
        $image  = imagecreatetruecolor($width, $height);
        $bg         = imagecolorallocate($image, 30, 30, 60);
        $textColor  = imagecolorallocate($image, 255, 255, 255);
        $noiseColor = imagecolorallocate($image, 100, 100, 160);
        imagefill($image, 0, 0, $bg);
        for ($i = 0; $i < 500; $i++) {
            imagesetpixel($image, random_int(0, $width), random_int(0, $height), $noiseColor);
        }
        for ($i = 0; $i < 5; $i++) {
            imageline($image,
                random_int(0, $width), random_int(0, $height),
                random_int(0, $width), random_int(0, $height),
                $noiseColor
            );
        }
        for ($i = 0; $i < strlen($code); $i++) {
            $x        = 15 + $i * 22;
            $y        = random_int(30, 40);
            $fontSize = random_int(4, 5);
            imagestring($image, $fontSize, $x, $y - 10, $code[$i], $textColor);
        }
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);
        return new Response($imageData, 200, ['Content-Type' => 'image/png']);
    }
}