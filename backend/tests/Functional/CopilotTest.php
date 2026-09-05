<?php

namespace App\Tests\Functional;

use App\Ai\ClaudeClientInterface;
use App\Ai\ClaudeResponse;
use App\Tests\Fake\FakeClaudeClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CopilotTest extends WebTestCase
{
    use ApiTestTrait;

    public function testAskDispatchesToolCallAndReturnsFinalAnswer(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->registerCompany($client, 'Boutique Awa', 'copilot@boutique-awa.sn');
        $token = $this->login($client, 'copilot@boutique-awa.sn');

        $this->apiRequest($client, 'POST', '/api/products', $token, [
            'name' => 'T-shirt',
            'unitPrice' => '7500',
            'stockQuantity' => 50,
        ]);
        $product = $this->jsonResponse($client);

        $this->apiRequest($client, 'POST', '/api/sales', $token, [
            'paymentMethod' => 'cash',
            'discount' => '0',
            'items' => [['productId' => $product['id'], 'quantity' => 2]],
        ]);

        $fakeClient = new FakeClaudeClient(
            new ClaudeResponse('tool_use', [
                ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'get_sales_summary', 'input' => []],
            ]),
            new ClaudeResponse('end_turn', [
                ['type' => 'text', 'text' => 'Votre chiffre d\'affaires ce mois-ci est de 15 000 FCFA.'],
            ]),
        );
        static::getContainer()->set(ClaudeClientInterface::class, $fakeClient);

        $this->apiRequest($client, 'POST', '/api/copilot/ask', $token, [
            'question' => 'Combien ai-je vendu ce mois-ci ?',
        ]);

        self::assertResponseIsSuccessful();
        $body = $this->jsonResponse($client);
        self::assertSame('Votre chiffre d\'affaires ce mois-ci est de 15 000 FCFA.', $body['answer']);

        self::assertCount(2, $fakeClient->calls);

        $toolNames = array_column($fakeClient->calls[0]['tools'], 'name');
        self::assertContains('get_sales_summary', $toolNames);
        self::assertContains('get_customers_with_unpaid_balance', $toolNames);

        $secondCallMessages = $fakeClient->calls[1]['messages'];
        $toolResultMessage = end($secondCallMessages);
        $toolResultContent = json_decode($toolResultMessage->content[0]['content'], true);
        self::assertSame('15000.00', $toolResultContent['revenue']);
    }

    public function testAnalyzeActivityEmbedsRealDataInThePrompt(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $this->registerCompany($client, 'Boutique Awa', 'analyze@boutique-awa.sn');
        $token = $this->login($client, 'analyze@boutique-awa.sn');

        $this->apiRequest($client, 'POST', '/api/expenses', $token, [
            'category' => 'transport',
            'amount' => '3000',
            'expenseDate' => (new \DateTimeImmutable())->format('Y-m-d').'T00:00:00+00:00',
        ]);

        $fakeClient = new FakeClaudeClient(
            new ClaudeResponse('end_turn', [
                ['type' => 'text', 'text' => "📈 Performance\nCA nul ce mois-ci."],
            ]),
        );
        static::getContainer()->set(ClaudeClientInterface::class, $fakeClient);

        $this->apiRequest($client, 'POST', '/api/copilot/analyze', $token);

        self::assertResponseIsSuccessful();
        $body = $this->jsonResponse($client);
        self::assertStringContainsString('Performance', $body['report']);

        self::assertCount(1, $fakeClient->calls);
        $prompt = $fakeClient->calls[0]['messages'][0]->content[0]['text'];
        self::assertStringContainsString('"total": "3000.00"', $prompt);
        self::assertStringContainsString('Boutique Awa', $fakeClient->calls[0]['system']);
    }
}
