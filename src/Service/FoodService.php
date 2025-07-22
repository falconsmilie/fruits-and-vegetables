<?php

namespace App\Service;

use App\Domain\Model\Food;
use App\Domain\Repository\FoodRepositoryInterface;
use App\DTO\AddFoodRequest;
use App\Exception\FoodFactoryException;
use App\Exception\FoodRepositoryException;
use App\Exception\FoodServiceException;
use App\Factory\FoodFactory;
use Symfony\Component\HttpFoundation\Response;

readonly class FoodService
{
    public function __construct(
        private FoodRepositoryInterface $repository,
        private FoodFactory $foodFactory
    ) {
    }

    /**
     * @throws FoodServiceException
     */
    public function addFood(Food $food): void
    {
        try {
            $this->repository->save($food);
        } catch (FoodRepositoryException $e) {
            throw new FoodServiceException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param AddFoodRequest[] $dtos
     * @throws FoodServiceException
     */
    public function bulkInsert(array $dtos): void
    {
        $foods = [];

        foreach ($dtos as $dto) {
            if (!$dto instanceof AddFoodRequest) {
                throw new FoodServiceException('Invalid DTO provided.');
            }

            try {
                $food = $this->foodFactory->fromAddRequest($dto);
            } catch (FoodFactoryException $e) {
                throw new FoodServiceException($e->getMessage(), $e->getCode(), $e);
            }

            $foods[] = $food;
        }

        try {
            $this->repository->bulkInsert($foods);
        } catch (FoodRepositoryException $e) {
            throw new FoodServiceException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function removeFood(Food $food): void
    {
        $this->repository->remove($food);
    }

    /**
     * @return Food[]
     * @throws FoodServiceException
     */
    public function listFoodByType(string $type, ?string $filterName = null): array
    {
        if (!in_array($type, [Food::TYPE_FRUIT, Food::TYPE_VEGETABLE], true)) {
            throw new FoodServiceException('Invalid food type', Response::HTTP_BAD_REQUEST);
        }

        return $this->repository->findByType($type, $filterName);
    }
}
