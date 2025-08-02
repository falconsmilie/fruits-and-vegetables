<?php

namespace App\Service;

use App\Domain\Contract\FoodRepositoryInterface;
use App\Domain\Model\Food;
use App\DTO\AddFoodRequest;
use App\Exception\FoodFactoryException;
use App\Exception\FoodRepositoryException;
use App\Exception\FoodServiceException;
use App\Factory\FoodFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class FoodService
{
    public function __construct(
        private FoodRepositoryInterface $repository,
        private FoodFactory $foodFactory,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @throws FoodServiceException
     */
    public function add(Food $food): void
    {
        try {
            $this->repository->entityManager()->wrapInTransaction(function () use ($food) {
                $this->repository->save($food);
                $this->repository->flush();
            });
        } catch (FoodRepositoryException|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            throw new FoodServiceException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array<AddFoodRequest> $dtos
     * @throws FoodServiceException
     */
    public function bulkUpsert(array $dtos): void
    {
        if (empty($dtos)) {
            throw new FoodServiceException('Cannot insert empty food list.', Response::HTTP_BAD_REQUEST);
        }

        $foods = $this->convertDtosToFoods($dtos);

        try {
            $this->repository->entityManager()->wrapInTransaction(function () use ($foods) {
                $this->repository->bulkInsert($foods);
            });
        } catch (FoodRepositoryException|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            throw new FoodServiceException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws FoodServiceException
     */
    public function remove(Food $food): void
    {
        try {
            $this->repository->entityManager()->wrapInTransaction(function () use ($food) {
                $this->repository->remove($food);
                $this->repository->flush();
            });
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            throw new FoodServiceException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return array<Food>
     * @throws FoodServiceException
     */
    public function list(string $type, ?string $filterName = null): array
    {
        if (!Food::isValidType($type)) {
            throw new FoodServiceException('Invalid food type: ' . $type, Response::HTTP_BAD_REQUEST);
        }

        return $this->repository->findByType($type, $filterName);
    }

    /**
     * @param array<AddFoodRequest> $dtos
     * @throws FoodServiceException
     */
    private function convertDtosToFoods(array $dtos): array
    {
        $foods = [];

        foreach ($dtos as $dto) {
            if (!$dto instanceof AddFoodRequest) {
                throw new FoodServiceException('Invalid DTO provided for bulk insert.', Response::HTTP_BAD_REQUEST);
            }

            try {
                $food = $this->foodFactory->fromAddRequest($dto);
            } catch (FoodFactoryException $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);

                throw new FoodServiceException($e->getMessage(), $e->getCode(), $e);
            }

            $foods[] = $food;
        }

        return $foods;
    }
}
