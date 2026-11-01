<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class BaseController
{
    protected function makeSuccess(string $message, mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    protected function makeError(string $message, array $errors, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
    }
}
