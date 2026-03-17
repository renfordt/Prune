<?php

declare(strict_types=1);

namespace Renfordt\Prune\Output;

use Renfordt\Prune\Analyzer\ClassEntry;
use Renfordt\Prune\Blade\BladeEntry;

class JsonFormatter implements ReportFormatter
{
    public function format(Report $report): string
    {
        $data = [
            'classOrphans' => array_map(fn (ClassEntry $entry): array => [
                'fqcn' => $entry->fqcn,
                'file' => $entry->file,
                'line' => $entry->line,
            ], $report->classOrphans),
            'bladeOrphans' => array_map(fn (BladeEntry $entry): array => [
                'viewName' => $entry->viewName,
                'file' => $entry->file,
            ], $report->bladeOrphans),
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
