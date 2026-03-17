<?php

declare(strict_types=1);

namespace Renfordt\Prune\Output;

interface ReportFormatter
{
    public function format(Report $report): string;
}
