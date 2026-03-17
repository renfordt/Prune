<?php

declare(strict_types=1);

namespace Renfordt\Prune\Output;

use Renfordt\Prune\Analyzer\ClassEntry;
use Renfordt\Prune\Blade\BladeEntry;

class ConsoleFormatter implements ReportFormatter
{
    public function format(Report $report): string
    {
        if (! $report->hasOrphans()) {
            return 'No orphaned classes or Blade views found.';
        }

        $lines = [];

        if ($report->classOrphans !== []) {
            $lines[] = '';
            $lines[] = sprintf(' Found %d orphaned class%s:', count($report->classOrphans), count($report->classOrphans) === 1 ? '' : 'es');
            $lines[] = '';

            $maxFqcn = max(array_map(fn (ClassEntry $e): int => strlen($e->fqcn), $report->classOrphans));
            $maxFile = max(array_map(fn (ClassEntry $e): int => strlen($e->file), $report->classOrphans));

            $headerFqcn = str_pad('Class', max($maxFqcn, 5));
            $headerFile = str_pad('File', max($maxFile, 4));
            $headerLine = 'Line';

            $lines[] = sprintf('  %s  %s  %s', $headerFqcn, $headerFile, $headerLine);
            $lines[] = sprintf('  %s  %s  %s', str_repeat('-', max($maxFqcn, 5)), str_repeat('-', max($maxFile, 4)), '----');

            foreach ($report->classOrphans as $orphan) {
                $lines[] = sprintf(
                    '  %s  %s  %d',
                    str_pad($orphan->fqcn, max($maxFqcn, 5)),
                    str_pad($orphan->file, max($maxFile, 4)),
                    $orphan->line,
                );
            }

            $lines[] = '';
        }

        if ($report->bladeOrphans !== []) {
            $lines[] = '';
            $lines[] = sprintf(' Found %d orphaned Blade view%s:', count($report->bladeOrphans), count($report->bladeOrphans) === 1 ? '' : 's');
            $lines[] = '';

            $maxName = max(array_map(fn (BladeEntry $e): int => strlen($e->viewName), $report->bladeOrphans));
            $maxFile = max(array_map(fn (BladeEntry $e): int => strlen($e->file), $report->bladeOrphans));

            $headerName = str_pad('View Name', max($maxName, 9));
            $headerFile = str_pad('File', max($maxFile, 4));

            $lines[] = sprintf('  %s  %s', $headerName, $headerFile);
            $lines[] = sprintf('  %s  %s', str_repeat('-', max($maxName, 9)), str_repeat('-', max($maxFile, 4)));

            foreach ($report->bladeOrphans as $orphan) {
                $lines[] = sprintf(
                    '  %s  %s',
                    str_pad($orphan->viewName, max($maxName, 9)),
                    str_pad($orphan->file, max($maxFile, 4)),
                );
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
