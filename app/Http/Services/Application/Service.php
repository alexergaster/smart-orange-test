<?php

namespace App\Http\Services\Application;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Service
{
    private readonly int $batchSize;

    private const string APPLICATIONS_TABLE = 'applications';

    public function __construct()
    {
        $this->batchSize = (int)config('application.batch_size');
    }

    public function createImport(int $totalRows): array
    {
        $id = (string)Str::uuid();
        $now = now();

        DB::table('application_imports')->insert([
            'id' => $id,
            'total_rows' => $totalRows,
            'processed_rows' => 0,
            'processed_batches' => 0,
            'status' => 'processing',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'id' => $id,
            'total_rows' => $totalRows,
            'processed_rows' => 0,
            'processed_batches' => 0,
            'status' => 'processing',
        ];
    }

    public function getImport(string $id): array
    {
        $import = DB::table('application_imports')
            ->where('id', $id)
            ->first();

        abort_if(!$import, 404, 'Import not found.');

        return (array)$import;
    }

    /**
     * @throws \Throwable
     * @throws \JsonException
     */
    public function importBatch(string $importId, int $batchIndex, array $rows): array
    {
        if (!array_is_list($rows)) {
            abort(422, 'Rows must be a sequential array.');
        }

        $count = count($rows);

        if ($count < 1 || $count > $this->batchSize) {
            abort(422, 'Invalid batch size.');
        }

        $checksum = hash(
            'sha256',
            json_encode(
                $rows,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            )
        );

        try {
            return DB::transaction(function () use (
                $importId,
                $batchIndex,
                $rows,
                $count,
                $checksum
            ) {
                $import = DB::table('application_imports')
                    ->where('id', $importId)
                    ->lockForUpdate()
                    ->first();

                abort_if(!$import, 404, 'Import not found.');

                $existing = DB::table('application_import_batches')
                    ->where('import_id', $importId)
                    ->where('batch_index', $batchIndex)
                    ->first();

                if ($existing) {
                    abort_if(
                        $existing->checksum !== $checksum ||
                        (int)$existing->rows_count !== $count,
                        409,
                        'Batch content mismatch.'
                    );

                    return [
                        'duplicate' => true,
                        'processed_rows' => (int)$import->processed_rows,
                        'processed_batches' => (int)$import->processed_batches,
                        'imported_rows' => (int)$import->imported_rows,
                        'skipped_rows' => (int)$import->skipped_rows,
                        'batch_imported' => (int)$existing->imported_rows,
                        'batch_skipped' => (int)$existing->skipped_rows,
                        'total_rows' => (int)$import->total_rows,
                    ];
                }

                abort_if(
                    $import->status !== 'processing',
                    409,
                    'Import is not active.'
                );

                abort_if(
                    $batchIndex !== (int)$import->processed_batches,
                    409,
                    'Unexpected batch index.'
                );

                $offset = $batchIndex * $this->batchSize;
                $remaining = (int)$import->total_rows - $offset;

                abort_if(
                    $remaining <= 0,
                    409,
                    'Batch exceeds total rows.'
                );

                $expectedCount = min($this->batchSize, $remaining);

                abort_if(
                    $count !== $expectedCount,
                    422,
                    'Incorrect batch size.'
                );

                $now = now()->toDateTimeString();
                $records = [];

                foreach ($rows as $position => $row) {
                    $normalized = $this->normalizeRow($row);

                    $records[] = [
                        ...$normalized,
                        'import_id' => $importId,
                        'import_row_number' => $offset + $position + 1,
                        'updated_at' => $now,
                    ];
                }

                $inserted = DB::table(self::APPLICATIONS_TABLE)
                    ->insertOrIgnore($records);

                $skipped = $count - $inserted;

                DB::table('application_import_batches')->insert([
                    'import_id' => $importId,
                    'batch_index' => $batchIndex,
                    'rows_count' => $count,
                    'imported_rows' => $inserted,
                    'skipped_rows' => $skipped,
                    'checksum' => $checksum,
                ]);

                $processedRows = (int)$import->processed_rows + $count;
                $processedBatches = (int)$import->processed_batches + 1;
                $importedRows = (int)$import->imported_rows + $inserted;
                $skippedRows = (int)$import->skipped_rows + $skipped;

                DB::table('application_imports')
                    ->where('id', $importId)
                    ->update([
                        'processed_rows' => $processedRows,
                        'processed_batches' => $processedBatches,
                        'imported_rows' => $importedRows,
                        'skipped_rows' => $skippedRows,
                        'updated_at' => $now,
                    ]);

                return [
                    'duplicate' => false,
                    'processed_rows' => $processedRows,
                    'processed_batches' => $processedBatches,
                    'imported_rows' => $importedRows,
                    'skipped_rows' => $skippedRows,
                    'batch_imported' => $inserted,
                    'batch_skipped' => $skipped,
                    'total_rows' => (int)$import->total_rows,
                ];

            }, 3);
        } catch (QueryException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                abort(
                    409,
                    'Duplicate unique key in applications table.'
                );
            }

            throw $e;
        }
    }

    /**
     * @throws \Throwable
     */
    public function completeImport(string $id): array
    {
        return DB::transaction(function () use ($id) {
            $import = DB::table('application_imports')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            abort_if(!$import, 404, 'Import not found.');

            $total = (int)$import->total_rows;

            $expectedBatches = (int)ceil(
                $total / $this->batchSize
            );

            abort_if(
                (int)$import->processed_rows !== $total ||
                (int)$import->processed_batches !== $expectedBatches,
                409,
                'Not all batches have been received.'
            );


            $actual = DB::table(self::APPLICATIONS_TABLE)
                ->where('import_id', $id)
                ->count();

            $imported = (int)$import->imported_rows;
            $skipped = (int)$import->skipped_rows;


            abort_if(
                $imported + $skipped !== $total,
                409,
                'Not all rows have been processed.'
            );

            abort_if(
                $actual !== $imported,
                409,
                'Database row count mismatch.'
            );


            if ($import->status === 'processing') {
                DB::table('application_imports')
                    ->where('id', $id)
                    ->update([
                        'status' => 'completed',
                        'updated_at' => now(),
                    ]);
            } else {
                abort_if(
                    $import->status !== 'completed',
                    409,
                    'Import is not active.'
                );
            }

            return [
                'status' => 'completed',
                'expected' => $total,
                'processed' => $total,
                'imported' => $imported,
                'inserted' => $actual,
                'skipped' => $skipped,
            ];
        }, 3);
    }

    private function normalizeRow(array $row): array
    {
        return [
            'external_id' => trim((string)$row['external_id']),
            'created_at' => $row['created_at'] ?? null,
            'first_name' => trim((string)$row['first_name']),
            'last_name' => trim((string)$row['last_name']),
            'phone' => $this->nullableString($row['phone'] ?? null),
            'email' => $this->nullableString($row['email'] ?? null),
            'city' => $this->nullableString($row['city'] ?? null),
            'source' => $this->nullableString($row['source'] ?? null),
            'utm_campaign' => $this->nullableString(
                $row['utm_campaign'] ?? null
            ),
            'product' => $this->nullableString($row['product'] ?? null),
            'budget_uah' => $this->normalizeBudget(
                $row['budget_uah'] ?? null
            ),
            'status' => $this->nullableString($row['status'] ?? null),
            'manager' => $this->nullableString($row['manager'] ?? null),
            'comment' => $this->nullableString($row['comment'] ?? null),
            'next_contact_at' => $row['next_contact_at'] ?? null,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);

        return $value !== '' ? $value : null;
    }

    private function normalizeBudget(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string)$value;
    }
}
