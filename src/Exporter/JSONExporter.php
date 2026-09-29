<?php

namespace Skeletor\Exporter;

use Skeletor\Exporter\Contracts\ExporterInterface;
use Skeletor\Translator\Service\Translator;

class JSONExporter implements ExporterInterface
{
    public function __construct(private Translator $translator)
    {}

    public function export(array $data, string $filename = 'export.json'): void
    {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $columns = array_shift($data);
        $translatedColumns = [];
        foreach ($columns as $column) {
            $translatedColumns[] = $this->translator->translate($column);
        }
        $jsonData = [];
        foreach ($data as $row) {
            if(is_array($row)) {
                foreach($row as $key => $value) {
                    if(is_array($value)) {
                        $row[$key] = implode(',', $value);
                    }
                }
            }
            $jsonData[] = array_combine($translatedColumns, $row);
        }
        $json = json_encode($jsonData, JSON_PRETTY_PRINT);
        echo $json;
    }
}