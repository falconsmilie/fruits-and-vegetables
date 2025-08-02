<?php

namespace App\DTO;

use App\Domain\Model\Food;
use Symfony\Component\Validator\Constraints as Assert;

class AddFoodRequest
{
    #[Assert\NotBlank]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?int $quantity = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: [Food::UNIT_GRAM, Food::UNIT_KILOGRAM])]
    public ?string $unit = Food::UNIT_GRAM;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: [Food::TYPE_FRUIT, Food::TYPE_VEGETABLE])]
    public ?string $type = null;
}
