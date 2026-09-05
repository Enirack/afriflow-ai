<?php

namespace App\Ai;

final class ClaudeResponse
{
    public function __construct(
        public readonly string $stopReason,
        /** @var array<int, array<string, mixed>> */
        public readonly array $content,
    ) {
    }

    public function text(): string
    {
        $parts = [];
        foreach ($this->content as $block) {
            if ('text' === $block['type']) {
                $parts[] = $block['text'];
            }
        }

        return implode("\n", $parts);
    }

    /**
     * @return list<array{id: string, name: string, input: array<string, mixed>}>
     */
    public function toolUses(): array
    {
        $uses = [];
        foreach ($this->content as $block) {
            if ('tool_use' === $block['type']) {
                $uses[] = $block;
            }
        }

        return $uses;
    }
}
