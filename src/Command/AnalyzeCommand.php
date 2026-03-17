<?php

declare(strict_types=1);

namespace Renfordt\Prune\Command;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Renfordt\Prune\Analyzer\ClassMapBuilder;
use Renfordt\Prune\Analyzer\OrphanDetector;
use Renfordt\Prune\Analyzer\ReferenceScanner;
use Renfordt\Prune\Config\Configuration;
use Renfordt\Prune\Blade\BladeDirectiveScanner;
use Renfordt\Prune\Blade\BladeOrphanDetector;
use Renfordt\Prune\Blade\BladeReferenceScanner;
use Renfordt\Prune\Blade\BladeViewDiscovery;
use Renfordt\Prune\Output\ConsoleFormatter;
use Renfordt\Prune\Output\HtmlFormatter;
use Renfordt\Prune\Output\JsonFormatter;
use Renfordt\Prune\Output\Report;
use Renfordt\Prune\Output\ReportFormatter;
use Renfordt\Prune\Parser\FileParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

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

        $absolutePaths = array_map(
            fn (string $path): string => str_starts_with($path, '/') ? $path : $workingDir . '/' . $path,
            $paths,
        );

        $finder = new Finder();
        $finder->files()->in($absolutePaths)->name('*.php');

        foreach ($config->excludePaths as $exclude) {
            $finder->notPath($exclude);
        }

        $bladeOnly = (bool) $input->getOption('blade');
        $bladeEnabled = $bladeOnly || $config->bladeEnabled;
        $classEnabled = ! $bladeOnly;

        $parser = new FileParser();
        $classMapBuilder = new ClassMapBuilder();
        $referenceScanner = new ReferenceScanner();
        $bladeReferenceScanner = new BladeReferenceScanner();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($classMapBuilder);
        $traverser->addVisitor($referenceScanner);
        $traverser->addVisitor($bladeReferenceScanner);

        $fileCount = 0;
        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            if ($filePath === false) {
                continue;
            }

            $stmts = $parser->parse($filePath);
            if ($stmts === []) {
                continue;
            }

            $classMapBuilder->setCurrentFile($filePath);
            $referenceScanner->setCurrentFile($filePath);
            $traverser->traverse($stmts);
            $fileCount++;
        }

        $output->writeln(sprintf('Scanned %d files.', $fileCount), OutputInterface::VERBOSITY_VERBOSE);

        $classOrphans = [];
        if ($classEnabled) {
            $detector = new OrphanDetector();
            $classOrphans = $detector->detect($classMapBuilder->getClassMap(), $referenceScanner->getReferences());
        }

        $bladeOrphans = [];
        if ($bladeEnabled) {
            $bladeViewPaths = array_values(array_unique(array_merge(
                $absolutePaths,
                array_map(
                    fn (string $path): string => str_starts_with($path, '/') ? $path : $workingDir . '/' . $path,
                    $config->bladeViewPaths,
                ),
            )));

            $discovery = new BladeViewDiscovery();
            $allBladeViews = $discovery->discover($bladeViewPaths);

            $phpReferences = $bladeReferenceScanner->getReferences();

            $directiveScanner = new BladeDirectiveScanner();
            $bladeReferences = [];
            foreach ($allBladeViews as $entry) {
                $content = file_get_contents($entry->file);
                if ($content === false) {
                    continue;
                }
                $bladeReferences = array_merge($bladeReferences, $directiveScanner->scan($content));
            }

            $allReferences = array_values(array_unique(array_merge($phpReferences, $bladeReferences)));

            $bladeDetector = new BladeOrphanDetector();
            $bladeOrphans = $bladeDetector->detect($allBladeViews, $allReferences, $config->bladeExcludeViews);
        }

        $report = new Report($classOrphans, $bladeOrphans);

        $formatter = $this->createFormatter($format);
        $formattedReport = $formatter->format($report);

        /** @var ?string $outputFile */
        $outputFile = $input->getOption('output');

        if ($outputFile === null && $format !== 'console') {
            $pruneDir = $workingDir . '/.prune';
            if (! is_dir($pruneDir)) {
                mkdir($pruneDir, 0755, true);
            }
            $outputFile = $pruneDir . '/report.' . $format;
        }

        if ($outputFile !== null) {
            file_put_contents($outputFile, $formattedReport);
            $output->writeln(sprintf('Report written to %s', $outputFile));
        } else {
            $output->write($formattedReport);
        }

        if ($report->hasOrphans()) {
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
