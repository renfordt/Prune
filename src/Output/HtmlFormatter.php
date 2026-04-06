<?php

declare(strict_types=1);

namespace Renfordt\Prune\Output;

class HtmlFormatter implements ReportFormatter
{
    public function format(Report $report): string
    {
        $classRows = '';
        foreach ($report->classOrphans as $orphan) {
            $fqcn = htmlspecialchars($orphan->fqcn, ENT_QUOTES | ENT_HTML5);
            $file = htmlspecialchars($orphan->file, ENT_QUOTES | ENT_HTML5);
            $classRows .= "            <tr><td>{$fqcn}</td><td>{$file}</td><td>{$orphan->line}</td></tr>\n";
        }

        $bladeRows = '';
        foreach ($report->bladeOrphans as $orphan) {
            $viewName = htmlspecialchars($orphan->viewName, ENT_QUOTES | ENT_HTML5);
            $file = htmlspecialchars($orphan->file, ENT_QUOTES | ENT_HTML5);
            $bladeRows .= "            <tr><td>{$viewName}</td><td>{$file}</td></tr>\n";
        }

        $classCount = count($report->classOrphans);
        $bladeCount = count($report->bladeOrphans);

        $classSection = '';
        if ($classCount > 0) {
            $classSection = <<<HTML
                <h2>Classes</h2>
                <p class="summary">Found {$classCount} orphaned class(es)</p>
                <table>
                    <thead>
                        <tr><th>Class</th><th>File</th><th>Line</th></tr>
                    </thead>
                    <tbody>
            {$classRows}        </tbody>
                </table>
            HTML;
        }

        $bladeSection = '';
        if ($bladeCount > 0) {
            $bladeSection = <<<HTML
                <h2>Blade Views</h2>
                <p class="summary">Found {$bladeCount} orphaned Blade view(s)</p>
                <table>
                    <thead>
                        <tr><th>View Name</th><th>File</th></tr>
                    </thead>
                    <tbody>
            {$bladeRows}        </tbody>
                </table>
            HTML;
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Prune Report</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #333; padding: 2rem; }
                h1 { margin-bottom: 0.5rem; }
                h2 { margin-top: 2rem; margin-bottom: 0.5rem; }
                .summary { color: #666; margin-bottom: 1.5rem; }
                table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                th { background: #2d3748; color: #fff; text-align: left; padding: 0.75rem 1rem; }
                td { padding: 0.75rem 1rem; border-top: 1px solid #e2e8f0; }
                tr:hover td { background: #f7fafc; }
                code { background: #edf2f7; padding: 0.15rem 0.4rem; border-radius: 3px; font-size: 0.9em; }
            </style>
        </head>
        <body>
            <h1>Prune Report</h1>
        {$classSection}
        {$bladeSection}
        </body>
        </html>

        HTML;
    }
}
