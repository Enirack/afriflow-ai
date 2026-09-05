<?php

namespace App\Ai\Tool;

use App\Entity\Company;
use App\Service\BusinessStatsService;

final class GetTopCustomersTool implements BusinessToolInterface
{
    public function __construct(
        private readonly BusinessStatsService $stats,
    ) {
    }

    public function getName(): string
    {
        return 'get_top_customers';
    }

    public function getDefinition(): array
    {
        return [
            'name' => $this->getName(),
            'description' => "Retourne les meilleurs clients (montant dépensé et solde impayé) sur une période donnée.",
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    ...DateRangeResolver::schemaProperties(),
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Nombre de clients à retourner (défaut 5).',
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function execute(array $input, Company $company): array
    {
        [$from, $to] = DateRangeResolver::resolve($input);

        return $this->stats->topCustomers($from, $to, $input['limit'] ?? 5);
    }
}
