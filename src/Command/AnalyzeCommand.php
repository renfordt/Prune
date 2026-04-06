<?php

declare(strict_types=1);

namespace Renfordt\Prune\Command;

use Renfordt\Prune\Analyzer\PruneAnalyzer;
use Renfordt\Prune\Config\Configuration;
use Renfordt\Prune\Output\ConsoleFormatter;
use Renfordt\Prune\Output\HtmlFormatter;
use Renfordt\Prune\Output\JsonFormatter;
use Renfordt\Prune\Output\ReportFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'analyze', description: 'Find orphaned and unused PHP classes')]
class AnalyzeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('paths', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Directories to scan')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to config file')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format (console, json, html)')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file path (for json/html)')
            ->addOption('blade', null, InputOption::VALUE_NONE, 'Only scan Blade views (skip class analysis)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDir = (string) getcwd();

        /** @var ?string $configFile */
        $configFile = $input->getOption('config');
        $config = Configuration::load($workingDir, $configFile);

        /** @var list<string> $pathArgs */
        $pathArgs = $input->getArgument('paths');
        $paths = $pathArgs !== [] ? $pathArgs : $config->paths;

        /** @var ?string $formatOption */
        $formatOption = $input->getOption('format');
        $format = $formatOption ?? $config->format;

        $bladeOnly = (bool) $input->getOption('blade');

        $analyzer = new PruneAnalyzer();
        $result = $analyzer->analyze(
            config: $config,
            paths: $paths,
            classEnabled: ! $bladeOnly,
            bladeEnabled: $bladeOnly || $config->bladeEnabled,
            workingDir: $workingDir,
        );

        $output->writeln(sprintf('Scanned %d files.', $result->fileCount), OutputInterface::VERBOSITY_VERBOSE);

        $formatter = $this->createFormatter($format);
        $formattedReport = $formatter->format($result->report);

        /** @var ?string $outputFile */
        $outputFile = $input->getOption('output');

        if ($outputFile === null && $format !== 'console') {
            $pruneDir = $workingDir . '/.prune';
            if (! is_dir($pruneDir) && ! mkdir($pruneDir, 0755, true) && ! is_dir($pruneDir)) {
                $output->writeln(sprintf('<error>Failed to create output directory: %s</error>', $pruneDir));

                return Command::FAILURE;
            }
            $outputFile = $pruneDir . '/report.' . $format;
        }

        if ($outputFile !== null) {
            file_put_contents($outputFile, $formattedReport);
            $output->writeln(sprintf('Report written to %s', $outputFile));
        } else {
            $output->write($formattedReport);
        }

        if ($result->report->hasOrphans()) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function createFormatter(string $format): ReportFormatter
    {
        return match ($format) {
            'json' => new JsonFormatter(),
            'html' => new HtmlFormatter(),
            default => new ConsoleFormatter(),
        };
    }
}
