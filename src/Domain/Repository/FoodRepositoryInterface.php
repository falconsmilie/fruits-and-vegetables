<?php

namespace App\Domain\Repository;

use App\Domain\Model\Food;
use App\Exception\FoodRepositoryException;

interface FoodRepositoryInterface
{
    /**
     * @throws FoodRepositoryException
     */
    public function save(Food $food): void;

    public function remove(Food $food): void;

    /** @return Food[] */
    public function findByType(string $type, ?string $filterName = null): array;
}
