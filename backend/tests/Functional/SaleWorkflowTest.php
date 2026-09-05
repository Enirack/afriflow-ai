<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SaleWorkflowTest extends WebTestCase
{
    use ApiTestTrait;

    public function testCreatingASaleComputesTotalsAndPaymentsUpdateBalance(): void
    {
        $client = static::createClient();

        $this->registerCompany($client, 'Boutique Awa', 'sale-flow@boutique-awa.sn');
        $token = $this->login($client, 'sale-flow@boutique-awa.sn');

        $this->apiRequest($client, 'POST', '/api/products', $token, [
            'name' => 'T-shirt',
            'unitPrice' => '7500',
            'stockQuantity' => 50,
        ]);
        self::assertResponseStatusCodeSame(201);
        $product = $this->jsonResponse($client);

        $this->apiRequest($client, 'POST', '/api/customers', $token, [
            'name' => 'Mamadou Ba',
            'phone' => '771234567',
        ]);
        self::assertResponseStatusCodeSame(201);
        $customer = $this->jsonResponse($client);

        $this->apiRequest($client, 'POST', '/api/sales', $token, [
            'customerId' => $customer['id'],
            'paymentMethod' => 'mobile_money',
            'discount' => '0',
            'items' => [
                ['productId' => $product['id'], 'quantity' => 2],
            ],
        ]);
        self::assertResponseStatusCodeSame(201);
        $sale = $this->jsonResponse($client);

        self::assertSame('15000.00', $sale['totalAmount']);
        self::assertSame('0.00', $sale['amountPaid']);
        self::assertSame('unpaid', $sale['status']);

        $this->apiRequest($client, 'POST', '/api/payments', $token, [
            'saleId' => $sale['id'],
            'amount' => '10000',
            'method' => 'mobile_money',
        ]);
        self::assertResponseStatusCodeSame(201);

        $this->apiRequest($client, 'GET', '/api/sales/'.$sale['id'], $token);
        $updatedSale = $this->jsonResponse($client);

        self::assertSame('10000.00', $updatedSale['amountPaid']);
        self::assertSame('5000.00', $updatedSale['balanceDue']);
        self::assertSame('partially_paid', $updatedSale['status']);
    }
}
