<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback('validateRange')]
class StatsQuery
{
    public ?\DateTimeImmutable $from = null;

    public ?\DateTimeImmutable $to = null;

    #[Assert\Range(min: 1, max: 100)]
    public int $limit = 5;

    private const MAX_RANGE_DAYS = 366;

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    public function range(): array
    {
        $to = $this->to ?? new \DateTimeImmutable('now');
        $from = $this->from ?? new \DateTimeImmutable('first day of this month 00:00:00');

        return [$from, $to];
    }

    public function validateRange(ExecutionContextInterface $context): void
    {
        // Validate the *effective* range (defaults applied), not just the raw
        // fields: supplying only one of from/to must not bypass the cap below.
        [$from, $to] = $this->range();

        if ($from > $to) {
            $context->buildViolation('"from" doit être antérieur ou égal à "to".')
                ->atPath('from')
                ->addViolation();

            return;
        }

        $days = $from->diff($to)->days;
        if ($days > self::MAX_RANGE_DAYS) {
            $context->buildViolation(sprintf('La période demandée ne peut pas dépasser %d jours.', self::MAX_RANGE_DAYS))
                ->atPath('to')
                ->addViolation();
        }
    }
}
