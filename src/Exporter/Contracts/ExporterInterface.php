<?php

namespace Skeletor\Exporter\Contracts;

interface ExporterInterface
{
    public function export(array $data, string $filename): void;
}