<?php

namespace App\Http\Services\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

class SpreadsheetApplicationReader implements ApplicationReaderInterface
{
    public function getHeaders(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);

        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);

        $worksheet = $spreadsheet->getActiveSheet();

        $headers = $worksheet
            ->rangeToArray(
                'A1:O1',
                null,
                true,
                true,
                false
            )[0];

        return array_map(
            fn ($header) => trim((string) $header),
            $headers
        );
    }

    public function read(string $filePath): iterable
    {
        $reader = IOFactory::createReaderForFile($filePath);

        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);

        $worksheet = $spreadsheet->getActiveSheet();

        $headers = [];

        foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
            $values = [];

            foreach ($row->getCellIterator() as $cell) {
                $values[] = $cell->getValue();
            }

            if ($rowIndex === 1) {
                $headers = array_map(
                    fn ($header) => trim((string) $header),
                    $values
                );

                continue;
            }

            if ($this->isEmptyRow($values)) {
                continue;
            }

            yield array_combine($headers, $values);
        }
    }

    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
