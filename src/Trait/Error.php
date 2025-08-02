<?php

namespace App\Trait;

use Symfony\Component\Validator\ConstraintViolationListInterface;

trait Error
{
    protected function jsonError(string|array $messages): array
    {
        $messages = (array)$messages;

        $errorMessages = [];

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $callerClass = $trace[1]['class'] ?? static::class;
        $callerMethod = $trace[1]['function'] ?? '';

        $path = "$callerClass::$callerMethod";

        foreach ($messages as $message) {
            $errorMessages[] = new \App\DTO\Error($path, $message);
        }

        return $errorMessages;
    }

    protected function formatValidationErrors(ConstraintViolationListInterface $errors): array
    {
        $errorMessages = [];

        foreach ($errors as $error) {
            $errorMessages[] = new \App\DTO\Error(
                $error->getPropertyPath(),
                $error->getMessage(),
                $error->getCode()
            );
        }

        return $errorMessages;
    }
}
