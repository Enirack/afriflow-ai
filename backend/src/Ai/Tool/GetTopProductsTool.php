<?php

namespace App\Ai\Tool;

use App\Entity\Company;
use App\Service\BusinessStatsService;

final class GetTopProductsTool implements BusinessToolInterface
{
    public function __construct(
        private readonly BusinessStatsService $stats,
    ) {
    }

    public function getName(): string
    {
        return 'get_top_products';
    }

    public function getDefinition(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'Retourne les produits les plus vendus (quantité vendue et chiffre d\'affaires généré) sur une période donnée.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    ...DateRangeResolver::schemaProperties(),
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Nombre de produits à retourner (défaut 5).',
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function execute(array $input, Company $company): array
    {
        [$from, $to] = DateRangeResolver::resolve($input);

        return $this->stats->topProducts($from, $to, $input['limit'] ?? 5);
    }
}
