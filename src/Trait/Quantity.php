<?php

namespace App\Trait;

use App\Domain\Model\Food;

trait Quantity
{
    private const GRAMS_PER_KILOGRAM = 1000;

    protected function quantityToGrams(int $quantity, string $unit): int
    {
        return $unit === Food::UNIT_KILOGRAM
            ? (int)($quantity * self::GRAMS_PER_KILOGRAM)
            : $quantity;
    }
}
