<?php

namespace App\DTO;

readonly class ListFoodResponse
{
    public function __construct(
        public string $name,
        public float|int $quantity,
        public string $unit,
        public string $type,
    ) {}
}
