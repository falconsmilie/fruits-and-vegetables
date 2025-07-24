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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Throwable;

final class FoodRepository extends ServiceEntityRepository implements FoodRepositoryInterface
{
    private readonly Connection $connection;

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly FoodMapper $mapper,
        private readonly DbalHelper $dbalHelper,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($registry, EntityFood::class);
        $this->connection = $registry->getConnection();
    }

    public function entityManager(): EntityManagerInterface
    {
        return $this->registry->getManagerForClass(EntityFood::class);
    }

    /**
     * @throws FoodRepositoryException
     */
    public function flush(): void
    {
        try {
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException $e) {
            $this->logger->error('Unique constraint violation on flush', ['exception' => $e]);
            throw new FoodRepositoryException(
                'Duplicate entry detected',
                FoodRepositoryException::CONFLICT,
                $e
            );
        } catch (DBALException | ORMException | Throwable $e) {
            $this->logger->error('Database error on flush', ['exception' => $e]);
            throw new FoodRepositoryException(
                'Database error occurred',
                FoodRepositoryException::INTERNAL_SERVER_ERROR,
                $e
            );
        }
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
            $this->logger->error('Mapping error in save', ['exception' => $e]);
            throw new FoodRepositoryException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $this->getEntityManager()->persist($entity);
        } catch (ORMException | Throwable $e) {
            $this->logger->error('Persist error in save', ['exception' => $e]);
            throw new FoodRepositoryException(
                'Error persisting food entity',
                FoodRepositoryException::INTERNAL_SERVER_ERROR,
                $e
            );
        }
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
                throw new FoodRepositoryException('All items must be instances of Food.');
            }

            $values[] = [
                'name' => $food->getName(),
                'type' => $food->getType(),
                'quantity_in_grams' => $food->getQuantityInGrams(),
            ];
        }

        try {
            $this->dbalHelper->insertBatch($this->connection, EntityFood::TABLE_NAME, $values);
        } catch (Throwable $e) {
            $this->logger->error('DBAL error in bulkInsert', ['exception' => $e]);
            throw new FoodRepositoryException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws FoodRepositoryException
     */
    public function remove(DomainFood $food): void
    {
        $entity = $this->findEntityByNameAndType($food->getName(), $food->getType());

        if ($entity === null) {
            return;
        }

        try {
            $this->getEntityManager()->remove($entity);
        } catch (ORMException | Throwable $e) {
            $this->logger->error('Remove error', ['exception' => $e]);
            throw new FoodRepositoryException(
                'Error removing food entity',
                FoodRepositoryException::INTERNAL_SERVER_ERROR,
                $e
            );
        }
    }

    /**
     * @return DomainFood[]
     * @throws FoodRepositoryException
     */
    public function findByType(string $type, ?string $name = null): array
    {
        try {
            $qb = $this->createQueryBuilder('f')
                ->andWhere('f.type = :type')
                ->setParameter('type', $type);

            if ($name !== null) {
                $qb->andWhere('LOWER(f.name) LIKE LOWER(:name)')
                    ->setParameter('name', '%' . $name . '%');
            }

            $entities = $qb->getQuery()->getResult();
        } catch (Throwable $e) {
            $this->logger->error('Query error in findByType', ['exception' => $e]);
            throw new FoodRepositoryException(
                'Database query error',
                FoodRepositoryException::INTERNAL_SERVER_ERROR,
                $e
            );
        }

        $foods = [];

        foreach ($entities as $entity) {
            try {
                $foods[] = $this->mapper->entityToDomain($entity);
            } catch (FoodMapperException $e) {
                $this->logger->error('Mapping error in findByType', ['exception' => $e]);
            }
        }

        return $foods;
    }

    protected function findEntityByNameAndType(string $name, string $type): ?EntityFood
    {
        return $this->findOneBy(['name' => $name, 'type' => $type]);
    }
}
