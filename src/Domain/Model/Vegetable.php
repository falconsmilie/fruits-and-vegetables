<?php

namespace App\Domain\Model;

use App\Domain\Model\Food;

class Vegetable extends Food
{

    public function getType(): string
    {
        return 'vegetable';
    }
}
