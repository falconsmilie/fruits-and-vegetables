<?php

namespace App\Service;

use App\Domain\Model\Food;
use App\Domain\Repository\FoodRepositoryInterface;
use App\Exception\FoodRepositoryException;
use App\Exception\FoodServiceException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class FoodService
{
    private FoodRepositoryInterface $repository;

    public function __construct(FoodRepositoryInterface $repository)
    {
        $this->repository = $repository;
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
