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

    public function testACompanyCannotCreateASaleAgainstAnotherCompanysProductOrCustomer(): void
    {
        $client = static::createClient();

        $this->registerCompany($client, 'Boutique Awa', 'sale-tenant-a@boutique-awa.sn');
        $tokenA = $this->login($client, 'sale-tenant-a@boutique-awa.sn');

        $this->apiRequest($client, 'POST', '/api/products', $tokenA, [
            'name' => 'T-shirt',
            'unitPrice' => '7500',
            'stockQuantity' => 50,
        ]);
        $productA = $this->jsonResponse($client);

        $this->apiRequest($client, 'POST', '/api/customers', $tokenA, ['name' => 'Mamadou Ba']);
        $customerA = $this->jsonResponse($client);

        $this->registerCompany($client, 'Autre Boutique', 'sale-tenant-b@autre.sn');
        $tokenB = $this->login($client, 'sale-tenant-b@autre.sn');

        $this->apiRequest($client, 'POST', '/api/products', $tokenB, [
            'name' => 'Chaussures',
            'unitPrice' => '18000',
            'stockQuantity' => 10,
        ]);
        $productB = $this->jsonResponse($client);

        $this->apiRequest($client, 'POST', '/api/sales', $tokenB, [
            'paymentMethod' => 'cash',
            'discount' => '0',
            'items' => [['productId' => $productA['id'], 'quantity' => 1]],
        ]);
        self::assertResponseStatusCodeSame(404);

        $this->apiRequest($client, 'POST', '/api/sales', $tokenB, [
            'customerId' => $customerA['id'],
            'paymentMethod' => 'cash',
            'discount' => '0',
            'items' => [['productId' => $productB['id'], 'quantity' => 1]],
        ]);
        self::assertResponseStatusCodeSame(404);
    }

    public function testACompanyCannotRecordAPaymentAgainstAnotherCompanysSale(): void
    {
        $client = static::createClient();

        $this->registerCompany($client, 'Boutique Awa', 'payment-tenant-a@boutique-awa.sn');
        $tokenA = $this->login($client, 'payment-tenant-a@boutique-awa.sn');

        $this->apiRequest($client, 'POST', '/api/products', $tokenA, [
            'name' => 'T-shirt',
            'unitPrice' => '7500',
            'stockQuantity' => 50,
        ]);
        $productA = $this->jsonResponse($client);

        $this->apiRequest($client, 'POST', '/api/sales', $tokenA, [
            'paymentMethod' => 'cash',
            'discount' => '0',
            'items' => [['productId' => $productA['id'], 'quantity' => 1]],
        ]);
        $saleA = $this->jsonResponse($client);

        $this->registerCompany($client, 'Autre Boutique', 'payment-tenant-b@autre.sn');
        $tokenB = $this->login($client, 'payment-tenant-b@autre.sn');

        $this->apiRequest($client, 'POST', '/api/payments', $tokenB, [
            'saleId' => $saleA['id'],
            'amount' => '1000',
            'method' => 'cash',
        ]);
        self::assertResponseStatusCodeSame(404);
    }
}
