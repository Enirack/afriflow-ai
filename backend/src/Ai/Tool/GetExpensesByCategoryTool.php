<?php

namespace App\Ai\Tool;

use App\Entity\Company;
use App\Service\BusinessStatsService;

final class GetExpensesByCategoryTool implements BusinessToolInterface
{
    public function __construct(
        private readonly BusinessStatsService $stats,
    ) {
    }

    public function getName(): string
    {
        return 'get_expenses_by_category';
    }

    public function getDefinition(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'Retourne le total des dépenses regroupé par catégorie (transport, salaire, stock, loyer, marketing, fournisseurs, autre) sur une période donnée.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => DateRangeResolver::schemaProperties(),
                'required' => [],
            ],
        ];
    }

    public function execute(array $input, Company $company): array
    {
        [$from, $to] = DateRangeResolver::resolve($input);

        return $this->stats->expensesByCategory($from, $to);
    }
}
