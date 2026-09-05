<?php

namespace App\Tests\Fake;

use App\Ai\ClaudeClientInterface;
use App\Ai\ClaudeMessage;
use App\Ai\ClaudeResponse;

/**
 * Test double for ClaudeClientInterface: returns a pre-configured queue of
 * responses and records every call so tests can assert on what the copilot
 * sent (system prompt, tool definitions, tool results).
 */
final class FakeClaudeClient implements ClaudeClientInterface
{
    /** @var ClaudeResponse[] */
    private array $responseQueue;

    /**
     * @var list<array{system: string, messages: ClaudeMessage[], tools: array<int, array<string, mixed>>}>
     */
    public array $calls = [];

    public function __construct(ClaudeResponse ...$responses)
    {
        $this->responseQueue = $responses;
    }

    public function send(string $system, array $messages, array $tools = []): ClaudeResponse
    {
        $this->calls[] = ['system' => $system, 'messages' => $messages, 'tools' => $tools];

        $response = array_shift($this->responseQueue);
        \assert($response instanceof ClaudeResponse);

        return $response;
    }
}
