<?php

namespace App\Http\Controllers\Application;

use Illuminate\Http\JsonResponse;

class CompleteController extends BaseController
{
    /**
     * @throws \Throwable
     */
    public function __invoke(string $id): JsonResponse
    {
        return response()->json($this->service->completeImport($id));
    }
}
