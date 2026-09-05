<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait ApiTestTrait
{
    private function registerCompany(KernelBrowser $client, string $companyName, string $email, string $password = 'password123', string $fullName = 'Test User'): void
    {
        $client->request('POST', '/api/register', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'companyName' => $companyName,
            'fullName' => $fullName,
            'email' => $email,
            'password' => $password,
        ]));
    }

    private function login(KernelBrowser $client, string $email, string $password = 'password123'): string
    {
        $client->request('POST', '/api/login_check', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);

        return $data['token'];
    }

    private function apiRequest(KernelBrowser $client, string $method, string $uri, string $token, ?array $payload = null): void
    {
        $client->request(
            $method,
            $uri,
            server: [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ],
            content: null !== $payload ? json_encode($payload) : null,
        );
    }

    private function jsonResponse(KernelBrowser $client): array
    {
        return json_decode($client->getResponse()->getContent(), true);
    }
}
