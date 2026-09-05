<?php

namespace App\Ai\Tool;

final class DateRangeResolver
{
    private const MAX_RANGE_DAYS = 366;

    /**
     * @param array<string, mixed> $input
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    public static function resolve(array $input): array
    {
        $to = self::parse($input['to'] ?? null) ?? new \DateTimeImmutable('now');
        $from = self::parse($input['from'] ?? null) ?? new \DateTimeImmutable('first day of this month 00:00:00');

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        if ($from->diff($to)->days > self::MAX_RANGE_DAYS) {
            $from = $to->modify(sprintf('-%d days', self::MAX_RANGE_DAYS));
        }

        return [$from, $to];
    }

    /**
     * @return array<string, mixed>
     */
    public static function schemaProperties(): array
    {
        return [
            'from' => [
                'type' => 'string',
                'description' => "Date de début (AAAA-MM-JJ). Par défaut, le 1er du mois en cours.",
            ],
            'to' => [
                'type' => 'string',
                'description' => "Date de fin (AAAA-MM-JJ). Par défaut, aujourd'hui.",
            ],
        ];
    }

    private static function parse(mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value) || '' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
