<?php

namespace App\Exception;

use Exception;

class FoodRepositoryException extends Exception
{
    public const CONFLICT = 409;

    public const INTERNAL_SERVER_ERROR = 500;
}
