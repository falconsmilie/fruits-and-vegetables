<?php

namespace App\DTO;

readonly class ValidationError
{
    public function __construct(
        public string $property,
        public string $message,
        public ?string $code = null,
    ) {}
}
