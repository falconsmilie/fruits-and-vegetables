<?php

namespace App\Service;

use App\Domain\Model\Food as DomainFood;
use App\Domain\Model\Fruit;
use App\Domain\Model\Vegetable;
use App\Entity\Food as EntityFood;
use App\Exception\FoodMapperException;
use Symfony\Component\HttpFoundation\Response;

class FoodMapper
{
    /**
     * @throws FoodMapperException
     */
    public function entityToDomain(EntityFood $entity): DomainFood
    {
        $type = $entity->getType();
        $name = $entity->getName();
        $weight = $entity->getQuantityInGrams();

        if ($type === null || $name === null || $weight === null) {
            throw new FoodMapperException('Food has null property: ' . json_encode([
                    'type' => $type,
                    'name' => $name,
                    'weight' => $weight,
                ]),
                Response::HTTP_BAD_REQUEST
            );
        }

        return match ($entity->getType()) {
            DomainFood::TYPE_FRUIT => new Fruit($entity->getName(), $entity->getQuantityInGrams()),
            DomainFood::TYPE_VEGETABLE => new Vegetable($entity->getName(), $entity->getQuantityInGrams()),
            default => throw new FoodMapperException(
                'Unknown food type: ' . $entity->getType(),
                Response::HTTP_BAD_REQUEST
            ),
        };
    }

    /**
     * @throws FoodMapperException
     */
    public function domainToEntity(DomainFood $domain, ?EntityFood $entity = null): EntityFood
    {
        $type = $domain->getType();

        if (!in_array($type, [DomainFood::TYPE_FRUIT, DomainFood::TYPE_VEGETABLE], true)) {
            throw new FoodMapperException('Invalid food type: ' . $type, Response::HTTP_BAD_REQUEST);
        }

        if ($entity === null) {
            $entity = new EntityFood();
        }

        $entity->setName($domain->getName());
        $entity->setQuantityInGrams($domain->getQuantityInGrams());
        $entity->setType($type);

        return $entity;
    }
}
