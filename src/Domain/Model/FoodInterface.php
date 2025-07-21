<?php

namespace App\Domain\Model;

interface FoodInterface
{
    public function getName(): string;

    public function getQuantityInGrams(): int;

    public function getType(): string;
}
