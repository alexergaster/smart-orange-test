<?php

namespace App\Http\Controllers\Application;

use App\Http\Requests\Application\StoreRequest;
use Illuminate\Http\JsonResponse;

class Import extends BaseController
{
    public function __invoke(StoreRequest $request): JsonResponse
    {
        $file = $request->file('file');

        $result = $this->service->import($file->getRealPath(), $file->getClientOriginalExtension());

        return response()->json("Imported {$result['imported']} applications. Skipped {$result['skipped']} applications.");
    }
}
