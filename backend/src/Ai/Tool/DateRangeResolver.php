<?php

namespace App\Ai\Tool;

final class DateRangeResolver
{
    /**
     * @param array<string, mixed> $input
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    public static function resolve(array $input): array
    {
        $to = isset($input['to']) ? new \DateTimeImmutable($input['to']) : new \DateTimeImmutable('now');
        $from = isset($input['from'])
            ? new \DateTimeImmutable($input['from'])
            : new \DateTimeImmutable('first day of this month 00:00:00');

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
}
