<?php

namespace App\Domain\Model;

abstract class Food implements FoodInterface
{
    public const UNIT_GRAM = 'g';
    public const UNIT_KILOGRAM = 'kg';

    public const TYPE_FRUIT = 'fruit';
    public const TYPE_VEGETABLE = 'vegetable';

    public function __construct(private readonly string $name, private readonly int $quantityInGrams)
    {
    }

    abstract public function getType(): string;

    public static function isValidType(string $type): bool
    {
        return in_array($type, [self::TYPE_FRUIT, self::TYPE_VEGETABLE], true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantityInGrams(): int
    {
        return $this->quantityInGrams;
    }
}
