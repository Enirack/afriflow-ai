<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\Expense;
use App\Entity\Sale;
use App\Enum\SaleStatus;
use Doctrine\ORM\EntityManagerInterface;

final class BusinessStatsService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{from: string, to: string, salesCount: int, revenue: string, expensesTotal: string, estimatedProfit: string, balanceDue: string}
     */
    public function summary(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $sales = $this->salesInRange($from, $to);

        $revenue = '0.00';
        $balanceDue = '0.00';
        foreach ($sales as $sale) {
            $revenue = bcadd($revenue, $sale->getTotalAmount(), 2);
            $balanceDue = bcadd($balanceDue, $sale->getBalanceDue(), 2);
        }

        $expensesTotal = $this->expensesTotalInRange($from, $to);

        return [
            'from' => $from->format(DATE_ATOM),
            'to' => $to->format(DATE_ATOM),
            'salesCount' => \count($sales),
            'revenue' => $revenue,
            'expensesTotal' => $expensesTotal,
            'estimatedProfit' => bcsub($revenue, $expensesTotal, 2),
            'balanceDue' => $balanceDue,
        ];
    }

    /**
     * @return list<array{productId: int, name: string, quantitySold: int, revenue: string}>
     */
    public function topProducts(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 5): array
    {
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

        return \array_slice(array_values($aggregates), 0, $limit);
    }

    /**
     * @return list<array{customerId: int, name: string, salesCount: int, totalSpent: string, balanceDue: string}>
     */
    public function topCustomers(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 5): array
    {
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

        return \array_slice(array_values($aggregates), 0, $limit);
    }

    /**
     * Customers with an outstanding balance right now, regardless of when the sale happened.
     *
     * @return list<array{customerId: int, name: string, phone: ?string, balanceDue: string}>
     */
    public function unpaidCustomers(int $limit = 10): array
    {
        $sales = $this->entityManager->createQueryBuilder()
            ->select('s', 'i', 'pay', 'c')
            ->from(Sale::class, 's')
            ->leftJoin('s.items', 'i')
            ->leftJoin('s.payments', 'pay')
            ->leftJoin('s.customer', 'c')
            ->where('s.status != :paid')
            ->setParameter('paid', SaleStatus::Paid->value)
            ->getQuery()
            ->getResult();

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
                'phone' => $customer->getPhone(),
                'balanceDue' => '0.00',
            ];
            $aggregates[$id]['balanceDue'] = bcadd($aggregates[$id]['balanceDue'], $sale->getBalanceDue(), 2);
        }

        usort($aggregates, static fn (array $a, array $b) => bccomp($b['balanceDue'], $a['balanceDue'], 2));

        return \array_slice(array_values($aggregates), 0, $limit);
    }

    /**
     * @return list<array{category: string, total: string}>
     */
    public function expensesByCategory(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $expenses = $this->expensesInRange($from, $to);

        $totals = [];
        foreach ($expenses as $expense) {
            $category = $expense->getCategory()->value;
            $totals[$category] = bcadd($totals[$category] ?? '0.00', $expense->getAmount(), 2);
        }

        arsort($totals);

        $result = [];
        foreach ($totals as $category => $total) {
            $result[] = ['category' => $category, 'total' => $total];
        }

        return $result;
    }

    /**
     * @return list<array{date: string, revenue: string}>
     */
    public function revenueSeries(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
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

        return $series;
    }

    public function companyName(Company $company): string
    {
        return $company->getName();
    }

    /**
     * @return Sale[]
     */
    private function salesInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('s', 'i', 'p', 'pay', 'c')
            ->from(Sale::class, 's')
            ->leftJoin('s.items', 'i')
            ->leftJoin('i.product', 'p')
            ->leftJoin('s.payments', 'pay')
            ->leftJoin('s.customer', 'c')
            ->where('s.saleDate BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Expense[]
     */
    private function expensesInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(Expense::class, 'e')
            ->where('e.expenseDate BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    private function expensesTotalInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): string
    {
        $total = '0.00';
        foreach ($this->expensesInRange($from, $to) as $expense) {
            $total = bcadd($total, $expense->getAmount(), 2);
        }

        return $total;
    }
}
