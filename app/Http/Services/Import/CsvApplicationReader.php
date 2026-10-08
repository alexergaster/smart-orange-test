<?php

namespace App\Http\Services\Import;

use RuntimeException;

class CsvApplicationReader implements ApplicationReaderInterface
{
    public function getHeaders(string $filePath): array
    {
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open CSV file.');
        }

        $headers = fgetcsv($handle);

        fclose($handle);

        if ($headers === false) {
            throw new RuntimeException('CSV file is empty.');
        }

        return array_map(
            fn($header) => trim((string)$header),
            $headers
        );
    }

    public function read(string $filePath): iterable
    {
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open CSV file.');
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            throw new RuntimeException('CSV file is empty.');
        }

        $headers = array_map(
            fn($header) => trim((string)$header),
            $headers
        );

        while (($values = fgetcsv($handle)) !== false) {
            if ($this->isEmptyRow($values)) {
                continue;
            }

            yield array_combine($headers, $values);
        }

        fclose($handle);
    }

    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string)$value) !== '') {
                return false;
            }
        }

        return true;
    }
}
