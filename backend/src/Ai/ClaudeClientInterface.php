<?php

namespace App\Ai;

interface ClaudeClientInterface
{
    /**
     * @param ClaudeMessage[] $messages
     * @param array<int, array<string, mixed>> $tools
     */
    public function send(string $system, array $messages, array $tools = []): ClaudeResponse;
}
