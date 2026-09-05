<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthenticationTest extends WebTestCase
{
    use ApiTestTrait;

    public function testRegisterThenLoginThenMe(): void
    {
        $client = static::createClient();

        $this->registerCompany($client, 'Boutique Awa', 'awa@boutique-awa.sn');
        self::assertResponseStatusCodeSame(201);

        $token = $this->login($client, 'awa@boutique-awa.sn');
        self::assertNotEmpty($token);

        $this->apiRequest($client, 'GET', '/api/me', $token);
        self::assertResponseIsSuccessful();

        $me = $this->jsonResponse($client);
        self::assertSame('awa@boutique-awa.sn', $me['email']);
        self::assertSame('Boutique Awa', $me['company']['name']);
        self::assertContains('ROLE_ADMIN', $me['roles']);
    }

    public function testCannotRegisterTwiceWithSameEmail(): void
    {
        $client = static::createClient();

        $this->registerCompany($client, 'Boutique Awa', 'duplicate@boutique-awa.sn');
        self::assertResponseStatusCodeSame(201);

        $this->registerCompany($client, 'Autre Boutique', 'duplicate@boutique-awa.sn');
        self::assertResponseStatusCodeSame(409);
    }

    public function testLoginWithWrongPasswordIsRejected(): void
    {
        $client = static::createClient();

        $this->registerCompany($client, 'Boutique Awa', 'wrongpass@boutique-awa.sn');

        $client->request('POST', '/api/login_check', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'wrongpass@boutique-awa.sn',
            'password' => 'not-the-password',
        ]));

        self::assertResponseStatusCodeSame(401);
    }
}
