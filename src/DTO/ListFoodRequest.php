<?php
declare(strict_types=1);

namespace App\DTO;

use App\Domain\Model\Food;
use Symfony\Component\Validator\Constraints as Assert;

class ListFoodRequest
{
    #[Assert\Choice(choices: [Food::TYPE_FRUIT, Food::TYPE_VEGETABLE])]
    public ?string $type = null;

    public ?string $name = null;

    #[Assert\Choice(choices: [Food::UNIT_GRAM, Food::UNIT_KILOGRAM])]
    public string $unit = Food::UNIT_GRAM;
}
