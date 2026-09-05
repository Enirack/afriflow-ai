<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;

final class SecurityController
{
    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    public function __invoke(): never
    {
        throw new \LogicException('This route is handled by the json_login authenticator and should never be reached directly.');
    }
}
