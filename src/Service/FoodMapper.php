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
        $quantity = $entity->getQuantityInGrams();

        if ($type === null || $name === null || $quantity === null) {
            throw new FoodMapperException(sprintf(
                'Cannot map entity to domain: null value(s) found [type=%s, name=%s, weight=%s]',
                var_export($type, true),
                var_export($name, true),
                var_export($quantity, true)
            ), Response::HTTP_BAD_REQUEST);
        }

        return match ($type) {
            DomainFood::TYPE_FRUIT => new Fruit($name, $quantity),
            DomainFood::TYPE_VEGETABLE => new Vegetable($name, $quantity),
            default => throw new FoodMapperException(
                'Unknown food type: ' . $type,
                Response::HTTP_BAD_REQUEST
            ),
        };
    }

    /**
     * @throws FoodMapperException
     */
    public function domainToEntity(DomainFood $domain, ?EntityFood $entity = null): EntityFood
    {
        if (!$domain instanceof Fruit && !$domain instanceof Vegetable) {
            throw new FoodMapperException(
                'Unsupported domain model type: ' . get_class($domain),
                Response::HTTP_BAD_REQUEST
            );
        }

        $type = $domain->getType();

        if (!in_array($type, [DomainFood::TYPE_FRUIT, DomainFood::TYPE_VEGETABLE], true)) {
            throw new FoodMapperException('Invalid food type: ' . $type, Response::HTTP_BAD_REQUEST);
        }

        if ($entity === null) {
            $entity = new EntityFood;
        }

        $entity->setName($domain->getName());
        $entity->setQuantityInGrams($domain->getQuantityInGrams());
        $entity->setType($type);

        return $entity;
    }

    public function domainToDbArray(DomainFood $food): array
    {
        return [
            'name' => $food->getName(),
            'type' => $food->getType(),
            'quantity_in_grams' => $food->getQuantityInGrams(),
        ];
    }
}
