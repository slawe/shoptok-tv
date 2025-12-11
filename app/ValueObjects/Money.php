<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Encapsulates the logic around pricing.
 */
final class Money
{
    /** @var int $amountInCents */
    private int $amountInCents;

    /** @var string $currency */
    private string $currency;

    /**
     * Money constructor.
     *
     * @param int $amountInCents
     * @param string $currency
     */
    private function __construct(int $amountInCents, string $currency)
    {
        if ($amountInCents < 0) {
            throw new InvalidArgumentException('Amount cannot be negative.');
        }

        $this->amountInCents = $amountInCents;
        $this->currency = strtoupper($currency);
    }

    /**
     * Logic about price.
     * Conversion from string to integer cents.
     *
     * @param string $rawPrice
     * @param string $currency
     * @return self
     */
    public static function fromLocalizedString(string $rawPrice, string $currency = 'EUR'): self
    {
        // eg "€329.00", "€1,499.00", "€719.10 in shoptok"
        $normalized = trim($rawPrice);

        // remove anything that is not a number, comma, dot
        $normalized = preg_replace('/[^0-9,\.]/u', '', $normalized) ?? '';

        if ($normalized === '') {
            throw new InvalidArgumentException("Cannot parse price from: {$rawPrice}");
        }

        // If there is a comma, convert it to a dot (decimal separator)
        if (str_contains($normalized, ',')) {
            $normalized = str_replace('.', '', $normalized);   // remove the dots as a thousand separator
            $normalized = str_replace(',', '.', $normalized);  // comma -> decimal
        }

        $float = (float) $normalized;
        $cents = (int) round($float * 100);

        return new self($cents, $currency);
    }

    /**
     * Amount in cents.
     *
     * @param int $amountInCents
     * @param string $currency
     * @return self
     */
    public static function fromCents(int $amountInCents, string $currency = 'EUR'): self
    {
        return new self($amountInCents, $currency);
    }

    /**
     * Amount in cents.
     *
     * @return int
     */
    public function amountInCents(): int
    {
        return $this->amountInCents;
    }

    /**
     * Get currency.
     *
     * @return string
     */
    public function currency(): string
    {
        return $this->currency;
    }

    /**
     * Formatted price.
     *
     * @param int $decimals
     * @return string
     */
    public function formatted(int $decimals = 2): string
    {
        return number_format($this->amountInCents / 100, $decimals, ',', '.') . ' ' . $this->currency;
    }
}
