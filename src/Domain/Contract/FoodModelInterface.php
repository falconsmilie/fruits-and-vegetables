<?php

namespace App\Domain\Contract;

interface FoodModelInterface
{
    public function getName(): string;

    public function getQuantityInGrams(): int;

    public function getType(): string;
}
