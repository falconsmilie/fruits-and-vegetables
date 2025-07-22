<?php

namespace App\Repository;

use App\Domain\Model\Food as DomainFood;
use App\Domain\Repository\FoodRepositoryInterface;
use App\Entity\Food as EntityFood;
use App\Exception\FoodMapperException;
use App\Exception\FoodRepositoryException;
use App\Service\FoodMapper;
use App\Util\DbalHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Throwable;

class FoodRepository extends ServiceEntityRepository implements FoodRepositoryInterface
{
    private object $connection;

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly FoodMapper $mapper,
        private readonly DbalHelper $dbalHelper,
    ) {
        parent::__construct($registry, EntityFood::class);

        $this->connection = $registry->getConnection();
    }

    /**
     * @throws FoodRepositoryException
     */
    public function save(DomainFood $food): void
    {
        $entity = $this->findEntityByNameAndType($food->getName(), $food->getType()) ?? new EntityFood();

        try {
            $entity = $this->mapper->domainToEntity($food, $entity);
        } catch (FoodMapperException $e) {
            throw new FoodRepositoryException($e->getMessage(), $e->getCode(), $e);
        }

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws FoodRepositoryException
     */
    public function bulkInsert(array $foods): void
    {
        if (empty($foods)) {
            return;
        }

        $values = [];

        foreach ($foods as $food) {
            if (!$food instanceof DomainFood) {
                throw new InvalidArgumentException('All items must be instances of Food.');
            }

            $values[] = [
                'name' => $food->getName(),
                'type' => $food->getType(),
                'quantity_in_grams' => $food->getQuantityInGrams(),
            ];
        }

        $this->connection->beginTransaction();

        try {
            $this->dbalHelper->insertBatch($this->connection, 'food', $values);
            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw new FoodRepositoryException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function remove(DomainFood $food): void
    {
        $entity = $this->findEntityByNameAndType($food->getName(), $food->getType());

        if ($entity !== null) {
            $this->getEntityManager()->remove($entity);
            $this->getEntityManager()->flush();
        }
    }

    public function findByType(string $type, ?string $filterName = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.type = :type')
            ->setParameter('type', $type);

        if ($filterName !== null) {
            $qb->andWhere('LOWER(f.name) LIKE LOWER(:name)')
                ->setParameter('name', '%' . $filterName . '%');
        }

        $entities = $qb->getQuery()->getResult();

        $foods = [];

        foreach ($entities as $entity) {
            try {
                $foods[] = $this->mapper->entityToDomain($entity);
            } catch (FoodMapperException $e) {
                // TODO: Handle exception (log, skip, or rethrow)
                continue;
            }
        }

        return $foods;
    }

    private function findEntityByNameAndType(string $name, string $type): ?EntityFood
    {
        return $this->findOneBy(['name' => $name, 'type' => $type]);
    }
}
