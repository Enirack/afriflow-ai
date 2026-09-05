<?php

namespace App\Dto;

class StatsQuery
{
    public ?\DateTimeImmutable $from = null;

    public ?\DateTimeImmutable $to = null;

    public int $limit = 5;

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    public function range(): array
    {
        $to = $this->to ?? new \DateTimeImmutable('now');
        $from = $this->from ?? new \DateTimeImmutable('first day of this month 00:00:00');

        return [$from, $to];
    }
}
