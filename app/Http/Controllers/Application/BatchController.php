<?php

namespace App\Http\Controllers\Application;

use App\Http\Requests\Application\BatchRequest;
use Illuminate\Http\JsonResponse;

class BatchController extends BaseController
{
    /**
     * @throws \JsonException
     */
    public function __invoke(BatchRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        $result = $this->service->importBatch($id, (int)$data['batch_index'], $data['rows']);

        return response()->json($result);
    }
}
