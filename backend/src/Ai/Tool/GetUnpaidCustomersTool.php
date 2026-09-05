<?php

namespace App\Ai\Tool;

use App\Entity\Company;
use App\Service\BusinessStatsService;

final class GetUnpaidCustomersTool implements BusinessToolInterface
{
    public function __construct(
        private readonly BusinessStatsService $stats,
    ) {
    }

    public function getName(): string
    {
        return 'get_customers_with_unpaid_balance';
    }

    public function getDefinition(): array
    {
        return [
            'name' => $this->getName(),
            'description' => "Retourne la liste des clients qui ont un solde impayé actuellement, "
                ."tous historiques confondus (pas limité à une période).",
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Nombre de clients à retourner (défaut 10).',
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function execute(array $input, Company $company): array
    {
        return $this->stats->unpaidCustomers($input['limit'] ?? 10);
    }
}
