<?php
namespace App\Factory;

use App\Domain\Model\Fruit;
use App\Domain\Model\Vegetable;
use App\DTO\AddFoodRequest;
use App\Domain\Model\Food;
use App\Exception\FoodFactoryException;

readonly class FoodFactory
{
    private const GRAMS_PER_KILOGRAM = 1000;

    public function __construct() {
    }

    /**
     * @throws FoodFactoryException
     */
    public function fromAddRequest(AddFoodRequest $dto): Food
    {
        $quantityInGrams = $this->quantityToGrams($dto->quantity, $dto->unit);

        return match ($dto->type) {
            Food::TYPE_FRUIT => new Fruit($dto->name, $quantityInGrams),
            Food::TYPE_VEGETABLE => new Vegetable($dto->name, $quantityInGrams),
            default => throw new FoodFactoryException('Invalid food type: ' . $dto->type),
        };
    }

    /**
     * Converts an array of AddFoodRequest DTOs to an array of Food domain objects
     *
     * @param AddFoodRequest[] $dtos
     * @return Food[]
     * @throws FoodFactoryException
     */
    public function fromAddRequestArray(array $dtos): array
    {
        $foods = [];
        foreach ($dtos as $dto) {
            if (!$dto instanceof AddFoodRequest) {
                throw new FoodFactoryException('Invalid AddFoodRequestDTO in array');
            }

            $foods[] = $this->fromAddRequest($dto);
        }

        return $foods;
    }

    private function quantityToGrams(int $quantity, string $unit): int
    {
        return $unit === Food::UNIT_KILOGRAM
            ? (int)($quantity * self::GRAMS_PER_KILOGRAM)
            : $quantity;
    }
}
