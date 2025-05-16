<?php

namespace App\Exceptions;

class Handler
{
    public function render($request, Throwable $exception)
{
    if ($request->expectsJson()) {

        if ($exception instanceof ValidationException) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $exception->errors(),
            ], 422);
        }

        return response()->json([
            'status' => false,
            'message' => $exception->getMessage() ?: 'Server Error',
        ], method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500);
    }

    return parent::render($request, $exception);
}

}
?>