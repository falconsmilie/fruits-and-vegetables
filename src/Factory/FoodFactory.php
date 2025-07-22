<?php
namespace App\Factory;

use App\Domain\Model\Fruit;
use App\Domain\Model\Vegetable;
use App\DTO\AddFoodRequest;
use App\Domain\Model\Food;
use App\Exception\FoodFactoryException;
use App\Service\FoodMapper;
use Exception;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class FoodFactory
{
    private const GRAMS_PER_KILOGRAM = 1000;

    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface  $validator
    ) {

    }

    /**
     * @return array{valid: array, errors: array}
     */
    public function create(array $data): array
    {
        $valid = [];
        $errors = [];

        foreach ($data as $index => $item) {
            try {
                $food = $this->serializer->denormalize($item, AddFoodRequest::class);
            } catch (Exception $e) {
                $errors[$index][] = ['property' => '', 'message' => 'Deserialization error: ' . $e->getMessage()];
                continue;
            }

            $violations = $this->validator->validate($food);
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[$index][] = [
                        'property' => $violation->getPropertyPath(),
                        'message' => $violation->getMessage()
                    ];
                }
                continue;
            }

            $quantityInGrams = $food->unit === Food::UNIT_KILOGRAM
                ? (int) ($food->quantity * self::GRAMS_PER_KILOGRAM)
                : (int) $food->quantity;

            $valid[] = match ($food->type) {
                Food::TYPE_FRUIT => new Fruit($food->name, $quantityInGrams),
                Food::TYPE_VEGETABLE => new Vegetable($food->name, $quantityInGrams),
            };
        }

        return ['food' => $valid, 'errors' => $errors];
    }

    /**
     * @throws FoodFactoryException
     */
    public function fromAddRequest(AddFoodRequest $dto): Food
    {
        $quantityInGrams = $dto->unit === Food::UNIT_KILOGRAM
            ? (int)($dto->quantity * 1000)
            : (int)$dto->quantity;

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
            $foods[] = $this->fromAddRequest($dto);
        }

        return $foods;
    }
}
