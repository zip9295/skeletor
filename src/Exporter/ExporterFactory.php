<?php

namespace Skeletor\Exporter;

use Skeletor\Exporter\Contracts\ExporterFactoryInterface;
use Skeletor\Exporter\Contracts\ExporterInterface;
use Skeletor\Translator\Service\Translator;

class ExporterFactory implements ExporterFactoryInterface
{
    public function __construct(private Translator $translator)
    {}

    public function createExporter(string $type): ExporterInterface
    {
        return match (strtolower($type)) {
            'csv' => new CSVExporter($this->translator),
            'json' => new JSONExporter($this->translator),
            default => throw new \InvalidArgumentException('Invalid exporter type'),
        };
    }
}