<?php

namespace App\Http\Services\Import;

interface ApplicationReaderInterface
{
    public function getHeaders(string $filePath): array;

    public function read(string $filePath): iterable;
}
