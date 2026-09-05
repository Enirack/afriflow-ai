<?php

namespace App\Ai;

/**
 * Provider-agnostic representation of a single content block, used both for
 * requests we send and responses we receive, so the copilot loop never
 * depends directly on the Anthropic SDK's response objects.
 */
final class ClaudeMessage
{
    public function __construct(
        public readonly string $role,
        /** @var array<int, array<string, mixed>> */
        public readonly array $content,
    ) {
    }

    public static function user(string $text): self
    {
        return new self('user', [['type' => 'text', 'text' => $text]]);
    }
}
