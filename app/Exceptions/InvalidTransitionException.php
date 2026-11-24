<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidTransitionException extends Exception
{
    public static function for(string $entity, string $from, string $to): self
    {
        return new self("No se permite la transición de {$entity} de '{$from}' a '{$to}'.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 422);
    }
}
