<?php

namespace App\Support;

use InvalidArgumentException;

final class MoneyDecimal
{
    public static function toCents(string $amount): string
    {
        if (preg_match('/^(\d+)(?:\.(\d+))?$/', $amount, $matches) !== 1) {
            throw new InvalidArgumentException('Money values must be non-negative decimal amounts.');
        }

        $fraction = $matches[2] ?? '';

        $cents = self::normalizeInteger(ltrim($matches[1], '0').str_pad(substr($fraction, 0, 2), 2, '0'));

        if (isset($fraction[2]) && (int) $fraction[2] >= 5) {
            $cents = self::add($cents, '1');
        }

        return $cents;
    }

    public static function add(string $left, string $right): string
    {
        $left = self::normalizeInteger($left);
        $right = self::normalizeInteger($right);
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;
        $carry = 0;
        $sum = '';

        while ($leftIndex >= 0 || $rightIndex >= 0 || $carry > 0) {
            $digitSum = ($leftIndex >= 0 ? (int) $left[$leftIndex--] : 0)
                + ($rightIndex >= 0 ? (int) $right[$rightIndex--] : 0)
                + $carry;
            $sum = ($digitSum % 10).$sum;
            $carry = intdiv($digitSum, 10);
        }

        return self::normalizeInteger($sum);
    }

    public static function subtract(string $left, string $right): string
    {
        if (self::compare($left, $right) < 0) {
            throw new InvalidArgumentException('Money subtraction cannot produce a negative amount.');
        }

        $left = self::normalizeInteger($left);
        $right = self::normalizeInteger($right);
        $rightIndex = strlen($right) - 1;
        $borrow = 0;
        $difference = '';

        for ($leftIndex = strlen($left) - 1; $leftIndex >= 0; $leftIndex--) {
            $digit = (int) $left[$leftIndex] - $borrow - ($rightIndex >= 0 ? (int) $right[$rightIndex--] : 0);
            $borrow = $digit < 0 ? 1 : 0;
            $difference = ($digit < 0 ? $digit + 10 : $digit).$difference;
        }

        return self::normalizeInteger($difference);
    }

    public static function compare(string $left, string $right): int
    {
        $left = self::normalizeInteger($left);
        $right = self::normalizeInteger($right);

        return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
    }

    public static function toAmount(string $cents): string
    {
        $cents = str_pad(self::normalizeInteger($cents), 3, '0', STR_PAD_LEFT);

        return substr($cents, 0, -2).'.'.substr($cents, -2);
    }

    public static function divideCentsRoundedUp(string $cents, int $divisor): string
    {
        if ($divisor < 1) {
            throw new InvalidArgumentException('The divisor must be greater than zero.');
        }

        $quotient = '';
        $remainder = 0;

        foreach (str_split(self::normalizeInteger($cents)) as $digit) {
            $value = ($remainder * 10) + (int) $digit;
            $quotient .= (string) intdiv($value, $divisor);
            $remainder = $value % $divisor;
        }

        $quotient = self::normalizeInteger($quotient);

        return $remainder > 0 ? self::add($quotient, '1') : $quotient;
    }

    public static function percentage(string $amountCents, string $targetCents): float
    {
        if (self::compare($amountCents, $targetCents) >= 0) {
            return 100.0;
        }

        $scaledPercentage = intdiv(((int) $amountCents) * 10000, (int) $targetCents);

        return intdiv($scaledPercentage, 100) + (($scaledPercentage % 100) / 100);
    }

    private static function normalizeInteger(string $value): string
    {
        $normalized = ltrim($value, '0');

        return $normalized === '' ? '0' : $normalized;
    }
}
