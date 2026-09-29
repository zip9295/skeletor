<?php

namespace Skeletor\Exporter;

use Skeletor\Exporter\Contracts\ExporterInterface;
use Skeletor\Translator\Service\Translator;

class CSVExporter implements ExporterInterface
{
    public function __construct(private Translator $translator)
    {}

    public function export(array $data, $filename = 'export.csv'): void
    {
        $output = fopen('php://output', 'w');
        if($output) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $translatedHeaders = [];
            foreach ($data[0] as $column) {
                $translatedHeaders[] = $this->translator->translate($column);
            }
            $data[0] = $translatedHeaders;
            foreach ($data as $row) {
                if(is_array($row)) {
                    $header = true;
                    foreach($row as $key => $value) {
                        if(is_array($value)) {
                            $row[$key] = implode(',', $value);
                        }
                        $header = false;
                    }
                }
                fputcsv($output, $row);
            }
            fclose($output);
        }
    }
}