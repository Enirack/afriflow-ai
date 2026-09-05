<?php

namespace App\Controller;

use App\Dto\StatsQuery;
use App\Entity\Expense;
use App\Entity\Sale;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stats')]
final class StatsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/summary', name: 'app_stats_summary', methods: ['GET'])]
    public function summary(#[MapQueryString] StatsQuery $query): JsonResponse
    {
        [$from, $to] = $query->range();
        $sales = $this->salesInRange($from, $to);

        $revenue = '0.00';
        $balanceDue = '0.00';
        foreach ($sales as $sale) {
            $revenue = bcadd($revenue, $sale->getTotalAmount(), 2);
            $balanceDue = bcadd($balanceDue, $sale->getBalanceDue(), 2);
        }

        $expensesTotal = $this->expensesTotalInRange($from, $to);

        return new JsonResponse([
            'from' => $from->format(DATE_ATOM),
            'to' => $to->format(DATE_ATOM),
            'salesCount' => \count($sales),
            'revenue' => $revenue,
            'expensesTotal' => $expensesTotal,
            'estimatedProfit' => bcsub($revenue, $expensesTotal, 2),
            'balanceDue' => $balanceDue,
        ]);
    }

    #[Route('/top-products', name: 'app_stats_top_products', methods: ['GET'])]
    public function topProducts(#[MapQueryString] StatsQuery $query): JsonResponse
    {
        [$from, $to] = $query->range();
        $sales = $this->salesInRange($from, $to);

        $aggregates = [];
        foreach ($sales as $sale) {
            foreach ($sale->getItems() as $item) {
                $product = $item->getProduct();
                $id = $product->getId();
                $aggregates[$id] ??= [
                    'productId' => $id,
                    'name' => $product->getName(),
                    'quantitySold' => 0,
                    'revenue' => '0.00',
                ];
                $aggregates[$id]['quantitySold'] += $item->getQuantity();
                $aggregates[$id]['revenue'] = bcadd($aggregates[$id]['revenue'], $item->getTotalPrice(), 2);
            }
        }

        usort($aggregates, static fn (array $a, array $b) => bccomp($b['revenue'], $a['revenue'], 2));

        return new JsonResponse(\array_slice(array_values($aggregates), 0, $query->limit));
    }

    #[Route('/top-customers', name: 'app_stats_top_customers', methods: ['GET'])]
    public function topCustomers(#[MapQueryString] StatsQuery $query): JsonResponse
    {
        [$from, $to] = $query->range();
        $sales = $this->salesInRange($from, $to);

        $aggregates = [];
        foreach ($sales as $sale) {
            $customer = $sale->getCustomer();
            if (!$customer) {
                continue;
            }

            $id = $customer->getId();
            $aggregates[$id] ??= [
                'customerId' => $id,
                'name' => $customer->getName(),
                'salesCount' => 0,
                'totalSpent' => '0.00',
                'balanceDue' => '0.00',
            ];
            $aggregates[$id]['salesCount']++;
            $aggregates[$id]['totalSpent'] = bcadd($aggregates[$id]['totalSpent'], $sale->getTotalAmount(), 2);
            $aggregates[$id]['balanceDue'] = bcadd($aggregates[$id]['balanceDue'], $sale->getBalanceDue(), 2);
        }

        usort($aggregates, static fn (array $a, array $b) => bccomp($b['totalSpent'], $a['totalSpent'], 2));

        return new JsonResponse(\array_slice(array_values($aggregates), 0, $query->limit));
    }

    #[Route('/revenue-series', name: 'app_stats_revenue_series', methods: ['GET'])]
    public function revenueSeries(#[MapQueryString] StatsQuery $query): JsonResponse
    {
        [$from, $to] = $query->range();
        $sales = $this->salesInRange($from, $to);

        $buckets = [];
        $period = new \DatePeriod($from, new \DateInterval('P1D'), $to->modify('+1 day'));
        foreach ($period as $day) {
            /** @var \DateTimeImmutable $day */
            $buckets[$day->format('Y-m-d')] = '0.00';
        }

        foreach ($sales as $sale) {
            $key = $sale->getSaleDate()->format('Y-m-d');
            $buckets[$key] = bcadd($buckets[$key] ?? '0.00', $sale->getTotalAmount(), 2);
        }

        ksort($buckets);

        $series = [];
        foreach ($buckets as $date => $revenue) {
            $series[] = ['date' => $date, 'revenue' => $revenue];
        }

        return new JsonResponse($series);
    }

    /**
     * @return Sale[]
     */
    private function salesInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('s', 'i', 'p', 'pay')
            ->from(Sale::class, 's')
            ->leftJoin('s.items', 'i')
            ->leftJoin('i.product', 'p')
            ->leftJoin('s.payments', 'pay')
            ->where('s.saleDate BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    private function expensesTotalInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): string
    {
        $expenses = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(Expense::class, 'e')
            ->where('e.expenseDate BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();

        $total = '0.00';
        foreach ($expenses as $expense) {
            $total = bcadd($total, $expense->getAmount(), 2);
        }

        return $total;
    }
}
