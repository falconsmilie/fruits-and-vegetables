<?php

namespace App\Domain\Contract;

use App\Domain\Model\Food;
use App\Exception\FoodRepositoryException;
use Doctrine\ORM\EntityManagerInterface;

interface FoodRepositoryInterface
{
    /**
     * @throws FoodRepositoryException
     */
    public function save(Food $food): void;

    /**
     * @throws FoodRepositoryException
     */
    public function bulkInsert(array $foods): void;

    public function remove(Food $food): void;

    /** @return array<Food> */
    public function findByType(string $type, ?string $name = null): array;

    public function flush(): void;

    public function entityManager(): EntityManagerInterface;
}
