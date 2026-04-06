<?php

declare(strict_types=1);

namespace Renfordt\Prune\Analyzer;

use Renfordt\Prune\Output\Report;

final readonly class AnalysisResult
{
    public function __construct(
        public Report $report,
        public int $fileCount,
    ) {
    }
}
