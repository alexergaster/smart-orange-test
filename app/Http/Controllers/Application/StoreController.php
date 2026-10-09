<?php

namespace App\Http\Controllers\Application;

use App\Http\Requests\Application\StoreRequest;
use Illuminate\Http\JsonResponse;

class StoreController extends BaseController
{
    public function __invoke(StoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->service->createImport((int)$data['total_rows']);

        return response()->json($result, 201);
    }
}
