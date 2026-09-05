<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StatsTest extends WebTestCase
{
    use ApiTestTrait;

    public function testSummaryAndTopListsAggregateAcrossSales(): void
    {
        $client = static::createClient();

        $this->registerCompany($client, 'Boutique Awa', 'stats@boutique-awa.sn');
        $token = $this->login($client, 'stats@boutique-awa.sn');

        $this->apiRequest($client, 'POST', '/api/products', $token, [
            'name' => 'T-shirt',
            'unitPrice' => '7500',
            'stockQuantity' => 50,
        ]);
        $tshirt = $this->jsonResponse($client);

        $this->apiRequest($client, 'POST', '/api/products', $token, [
            'name' => 'Chaussures',
            'unitPrice' => '18000',
            'stockQuantity' => 20,
        ]);
        $shoes = $this->jsonResponse($client);

        $this->apiRequest($client, 'POST', '/api/customers', $token, ['name' => 'Mamadou Ba']);
        $mamadou = $this->jsonResponse($client);

        $this->apiRequest($client, 'POST', '/api/customers', $token, ['name' => 'Fatou Sy']);
        $fatou = $this->jsonResponse($client);

        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $this->apiRequest($client, 'POST', '/api/expenses', $token, [
            'category' => 'transport',
            'amount' => '3000',
            'expenseDate' => $today.'T00:00:00+00:00',
        ]);

        $this->apiRequest($client, 'POST', '/api/sales', $token, [
            'customerId' => $mamadou['id'],
            'paymentMethod' => 'mobile_money',
            'discount' => '0',
            'items' => [['productId' => $tshirt['id'], 'quantity' => 2]],
        ]);

        $this->apiRequest($client, 'POST', '/api/sales', $token, [
            'customerId' => $fatou['id'],
            'paymentMethod' => 'cash',
            'discount' => '0',
            'items' => [['productId' => $shoes['id'], 'quantity' => 1]],
        ]);

        $this->apiRequest($client, 'GET', '/api/stats/summary', $token);
        self::assertResponseIsSuccessful();
        $summary = $this->jsonResponse($client);

        self::assertSame(2, $summary['salesCount']);
        self::assertSame('33000.00', $summary['revenue']);
        self::assertSame('3000.00', $summary['expensesTotal']);
        self::assertSame('30000.00', $summary['estimatedProfit']);
        self::assertSame('33000.00', $summary['balanceDue']);

        $this->apiRequest($client, 'GET', '/api/stats/top-products', $token);
        $topProducts = $this->jsonResponse($client);
        self::assertSame('Chaussures', $topProducts[0]['name']);
        self::assertSame('18000.00', $topProducts[0]['revenue']);
        self::assertSame('T-shirt', $topProducts[1]['name']);
        self::assertSame('15000.00', $topProducts[1]['revenue']);

        $this->apiRequest($client, 'GET', '/api/stats/top-customers', $token);
        $topCustomers = $this->jsonResponse($client);
        self::assertCount(2, $topCustomers);
        self::assertSame('Fatou Sy', $topCustomers[0]['name']);
        self::assertSame('18000.00', $topCustomers[0]['totalSpent']);
    }
}
