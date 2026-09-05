<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TenantIsolationTest extends WebTestCase
{
    use ApiTestTrait;

    public function testACompanyCannotSeeAnotherCompanysData(): void
    {
        $client = static::createClient();

        $this->registerCompany($client, 'Boutique Awa', 'tenant-a@boutique-awa.sn');
        $tokenA = $this->login($client, 'tenant-a@boutique-awa.sn');

        $this->apiRequest($client, 'POST', '/api/products', $tokenA, [
            'name' => 'Produit confidentiel',
            'unitPrice' => '1000',
            'stockQuantity' => 5,
        ]);
        self::assertResponseStatusCodeSame(201);
        $productA = $this->jsonResponse($client);

        $this->registerCompany($client, 'Autre Boutique', 'tenant-b@autre.sn');
        $tokenB = $this->login($client, 'tenant-b@autre.sn');

        $this->apiRequest($client, 'GET', '/api/products', $tokenB);
        $collection = $this->jsonResponse($client);
        self::assertSame(0, $collection['totalItems']);

        $this->apiRequest($client, 'GET', '/api/products/'.$productA['id'], $tokenB);
        self::assertResponseStatusCodeSame(404);
    }
}
