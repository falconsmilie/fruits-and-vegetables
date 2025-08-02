<?php
namespace App\Factory;

use App\Domain\Model\Fruit;
use App\Domain\Model\Vegetable;
use App\DTO\AddFoodRequest;
use App\Domain\Model\Food;
use App\Exception\FoodFactoryException;
use App\Trait\Quantity;

readonly class FoodFactory
{
    use Quantity;

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
     * @param array<AddFoodRequest> $dtos
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
}
