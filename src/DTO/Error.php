<?php

namespace App\DTO;

readonly class Error
{
    public function __construct(
        public string $property,
        public string $message,
        public ?string $code = null,
    ) {}
}
