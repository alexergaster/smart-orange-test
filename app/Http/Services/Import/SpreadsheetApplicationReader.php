<?php

namespace App\Http\Services\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

class SpreadsheetApplicationReader implements ApplicationReaderInterface
{
    public function getHeaders(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);

        $reader->setReadDataOnly(true);

        $filter = new ChunkReadFilter();
        $filter->setRows(1, 1);

        $reader->setReadFilter($filter);

        $spreadsheet = $reader->load($filePath);

        $worksheet = $spreadsheet->getActiveSheet();

        $headers = [];

        foreach ($worksheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $headers[] = trim((string) $cell->getValue());
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $headers;
    }
    public function read(string $filePath): iterable
    {
        $headers = $this->getHeaders($filePath);
        $batchSize = config('application.batch_size');

        $startRow = 2;

        while (true) {
            $reader = IOFactory::createReaderForFile($filePath);

            $reader->setReadDataOnly(true);

            $filter = new ChunkReadFilter();

            $filter->setRows($startRow, $batchSize);

            $reader->setReadFilter($filter);

            $spreadsheet = $reader->load($filePath);

            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();

            if ($startRow > $highestRow) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                break;
            }

            for ($rowNumber = $startRow; $rowNumber <= min($startRow + $batchSize - 1,$highestRow); $rowNumber++) {
                $values = [];

                foreach ($worksheet->getRowIterator($rowNumber, $rowNumber) as $row) {
                    foreach ($row->getCellIterator() as $cell) {
                        $values[] = $cell->getValue();
                    }
                }

                if ($this->isEmptyRow($values)) {
                    continue;
                }

                yield array_combine($headers, $values);
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $startRow += $batchSize;
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
