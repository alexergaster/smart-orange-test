<?php

namespace App\Http\Controllers\Application;

use Illuminate\Http\JsonResponse;

class ShowController extends BaseController
{
    public function __invoke(string $id): JsonResponse
    {
        return response()->json($this->service->getImport($id));
    }
}
