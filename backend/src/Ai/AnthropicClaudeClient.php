<?php

namespace App\Ai;

use Anthropic\Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class AnthropicClaudeClient implements ClaudeClientInterface
{
    private const MODEL = 'claude-opus-5';
    private const MAX_TOKENS = 4096;

    private readonly Client $client;

    public function __construct(#[Autowire(env: 'ANTHROPIC_API_KEY')] string $apiKey)
    {
        $this->client = new Client(apiKey: $apiKey);
    }

    public function send(string $system, array $messages, array $tools = []): ClaudeResponse
    {
        $message = $this->client->messages->create(
            model: self::MODEL,
            maxTokens: self::MAX_TOKENS,
            system: $system,
            messages: array_map(
                static fn (ClaudeMessage $m) => ['role' => $m->role, 'content' => $m->content],
                $messages,
            ),
            tools: $tools,
        );

        $content = [];
        foreach ($message->content as $block) {
            if ('text' === $block->type) {
                $content[] = ['type' => 'text', 'text' => $block->text];
            } elseif ('tool_use' === $block->type) {
                $content[] = ['type' => 'tool_use', 'id' => $block->id, 'name' => $block->name, 'input' => $block->input];
            }
        }

        return new ClaudeResponse($message->stopReason, $content);
    }
}
