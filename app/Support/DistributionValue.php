<?php

namespace App\Support;

final class DistributionValue
{
    public static function phone(?string $value): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $value);

        if (strlen($number) === 14 && str_starts_with($number, '0091')) {
            $number = substr($number, 4);
        } elseif (strlen($number) === 12 && str_starts_with($number, '91')) {
            $number = substr($number, 2);
        } elseif (strlen($number) === 11 && str_starts_with($number, '0')) {
            $number = substr($number, 1);
        }

        if (!preg_match('/^[6-9][0-9]{9}$/', $number)) {
            return null;
        }

        return '91' . $number;
    }

    public static function text(?string $value): string
    {
        return mb_strtolower(
            preg_replace('/\s+/u', ' ', trim((string) $value))
        );
    }

    public static function state(?string $value): string
    {
        $state = self::text($value);

        return match ($state) {
            'up', 'u.p.', 'u.p' => 'uttar pradesh',
            'mp', 'm.p.', 'm.p' => 'madhya pradesh',
            'nct of delhi', 'new delhi' => 'delhi',
            'orissa' => 'odisha',
            'uttaranchal' => 'uttarakhand',
            default => $state,
        };
    }
}