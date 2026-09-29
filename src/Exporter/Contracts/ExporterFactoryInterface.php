<?php

namespace Skeletor\Exporter\Contracts;

interface ExporterFactoryInterface
{
    public function createExporter(string $type): ExporterInterface;
}