<?php

namespace App\Ai\Tool;

use App\Entity\Company;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.ai_tool')]
interface BusinessToolInterface
{
    public function getName(): string;

    /**
     * @return array{name: string, description: string, inputSchema: array<string, mixed>}
     */
    public function getDefinition(): array;

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function execute(array $input, Company $company): array;
}
