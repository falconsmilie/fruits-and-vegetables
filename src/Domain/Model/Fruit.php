<?php

namespace App\Domain\Model;

use App\Domain\Model\Food;

class Fruit extends Food
{

    public function getType(): string
    {
        return self::TYPE_FRUIT;
    }
}
