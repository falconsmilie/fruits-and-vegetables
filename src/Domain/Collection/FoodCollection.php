<?php

namespace App\Domain\Collection;

use App\Domain\Model\FoodInterface;

class FoodCollection
{
    /** @var FoodInterface[] */
    private array $items = [];

    public function add(FoodInterface $food): void
    {
        $this->items[$food->getName()] = $food;
    }

    public function remove(string $name): void
    {
        unset($this->items[$name]);
    }

    /**
     * @return FoodInterface[]
     */
    public function list(): array
    {
        return array_values($this->items);
    }

    /**
     * @return FoodInterface[]
     */
    public function search(string $query): array
    {
        $query = mb_strtolower($query);

        return array_filter(
            $this->items,
            fn(FoodInterface $food) => mb_stripos($food->getName(), $query) !== false
        );
    }
}
