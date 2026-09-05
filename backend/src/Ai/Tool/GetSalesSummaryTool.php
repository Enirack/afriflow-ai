<?php

namespace App\Ai\Tool;

use App\Entity\Company;
use App\Service\BusinessStatsService;

final class GetSalesSummaryTool implements BusinessToolInterface
{
    public function __construct(
        private readonly BusinessStatsService $stats,
    ) {
    }

    public function getName(): string
    {
        return 'get_sales_summary';
    }

    public function getDefinition(): array
    {
        return [
            'name' => $this->getName(),
            'description' => "Retourne le chiffre d'affaires, le bénéfice estimé, le total des dépenses, "
                ."les créances (impayés) et le nombre de ventes pour une période donnée.",
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

        return $this->stats->summary($from, $to);
    }
}
