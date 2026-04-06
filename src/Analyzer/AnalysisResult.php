<?php

declare(strict_types=1);

namespace Renfordt\Prune\Analyzer;

use Renfordt\Prune\Output\Report;

final readonly class AnalysisResult
{
    /**
     * Constructor method for initializing the class with a Report object and file count.
     *
     * @param Report $report An instance of the Report class.
     * @param int $fileCount The number of files to process.
     * @return void
     */
    public function __construct(
        public Report $report,
        public int $fileCount,
    ) {
    }
}
