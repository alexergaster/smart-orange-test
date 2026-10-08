<?php

namespace App\Http\Services\Application;

use App\Http\Services\Import\CsvApplicationReader;
use App\Http\Services\Import\SpreadsheetApplicationReader;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Service
{
    private const array REQUIRED_HEADERS = [
        'external_id',
        'created_at',
        'first_name',
        'last_name',
        'phone',
        'email',
        'city',
        'source',
        'utm_campaign',
        'product',
        'budget_uah',
        'status',
        'manager',
        'comment',
        'next_contact_at',
    ];

    public function import(string $filePath, string $extension): array
    {
        $batchSize = config('application.batch_size');

        $reader = $this->getReader($extension);
        $headers = $reader->getHeaders($filePath);

        $this->validateHeaders($headers);

        $imported = 0;
        $skipped = 0;
        $batch = [];

        foreach ($reader->read($filePath) as $row) {
            $batch[] = $this->normalizeRow($row);

            if (count($batch) >= $batchSize) {
                $inserted = DB::table('applications')->insertOrIgnore($batch);

                $imported += $inserted;
                $skipped += count($batch) - $inserted;
                $batch = [];
            }
        }
        if ($batch !== []) {
            $inserted = DB::table('applications')->insertOrIgnore($batch);

            $imported += $inserted;
            $skipped += count($batch) - $inserted;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function getReader(string $extension): SpreadsheetApplicationReader|CsvApplicationReader
    {
        return match ($extension) {
            'csv' => new CsvApplicationReader(),
            'xlsx', 'xls' => new SpreadsheetApplicationReader(),
            default => throw new RuntimeException(
                'Unsupported file format.'
            ),
        };
    }

    private function validateHeaders(array $headers): void
    {
        $missingHeaders = array_diff(self::REQUIRED_HEADERS, $headers);

        if ($missingHeaders !== []) {
            throw new RuntimeException('Invalid file structure. Missing columns: ' . implode(', ', $missingHeaders));
        }
    }

    private function normalizeRow(array $row): array
    {
        return [
            'external_id' => trim($row['external_id']),
            'created_at' => $this->normalizeDate($row['created_at']),
            'first_name' => trim($row['first_name']),
            'last_name' => trim($row['last_name']),
            'phone' => $this->nullableString($row['phone']),
            'email' => $this->nullableString($row['email']),
            'city' => $this->nullableString($row['city']),
            'source' => $this->nullableString($row['source']),
            'utm_campaign' => $this->nullableString($row['utm_campaign']),
            'product' => $this->nullableString($row['product']),
            'budget_uah' => $this->normalizeBudget($row['budget_uah']),
            'status' => $this->nullableString($row['status']),
            'manager' => $this->nullableString($row['manager']),
            'comment' => $this->nullableString($row['comment']),
            'next_contact_at' => $this->normalizeDate($row['next_contact_at']),
        ];
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string)$value);

        return $value !== '' ? $value : null;
    }

    private function normalizeBudget($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace(' ', '', (string)$value);

        return (float)$value;
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return date(
            'Y-m-d H:i:s',
            strtotime((string)$value)
        );
    }


}
