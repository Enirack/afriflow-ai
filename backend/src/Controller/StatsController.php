<?php

namespace App\Controller;

use App\Dto\StatsQuery;
use App\Service\BusinessStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stats')]
final class StatsController extends AbstractController
{
    public function __construct(
        private readonly BusinessStatsService $stats,
    ) {
    }

    #[Route('/summary', name: 'app_stats_summary', methods: ['GET'])]
    public function summary(#[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] StatsQuery $query): JsonResponse
    {
        [$from, $to] = $query->range();

        return new JsonResponse($this->stats->summary($from, $to));
    }

    #[Route('/top-products', name: 'app_stats_top_products', methods: ['GET'])]
    public function topProducts(#[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] StatsQuery $query): JsonResponse
    {
        [$from, $to] = $query->range();

        return new JsonResponse($this->stats->topProducts($from, $to, $query->limit));
    }

    #[Route('/top-customers', name: 'app_stats_top_customers', methods: ['GET'])]
    public function topCustomers(#[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] StatsQuery $query): JsonResponse
    {
        [$from, $to] = $query->range();

        return new JsonResponse($this->stats->topCustomers($from, $to, $query->limit));
    }

    #[Route('/revenue-series', name: 'app_stats_revenue_series', methods: ['GET'])]
    public function revenueSeries(#[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] StatsQuery $query): JsonResponse
    {
        [$from, $to] = $query->range();

        return new JsonResponse($this->stats->revenueSeries($from, $to));
    }
}
