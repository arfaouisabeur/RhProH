<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class CaptchaService
{
    public function __construct(private RequestStack $requestStack) {}

    public function generate(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $this->requestStack->getSession()->set('captcha_code', strtoupper($code));
        return $code;
    }

    public function verify(string $input): bool
    {
        $session = $this->requestStack->getSession();
        $expected = $session->get('captcha_code');
        $session->remove('captcha_code');
        return strtoupper(trim($input)) === strtoupper($expected);
    }
}