<?php

declare(strict_types=1);

namespace Renfordt\Prune\Analyzer;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Renfordt\Prune\Blade\BladeDirectiveScanner;
use Renfordt\Prune\Blade\BladeOrphanDetector;
use Renfordt\Prune\Blade\BladeReferenceScanner;
use Renfordt\Prune\Blade\BladeViewDiscovery;
use Renfordt\Prune\Config\Configuration;
use Renfordt\Prune\Output\Report;
use Renfordt\Prune\Parser\FileParser;
use Symfony\Component\Finder\Finder;

class PruneAnalyzer
{
    /**
     * Analyzes the provided paths for PHP files and Blade templates, scans for orphans,
     * and generates an analysis report.
     *
     * @param Configuration $config Configuration settings including file extensions, excluded paths, and Blade-specific options.
     * @param list<string> $paths Relative or absolute paths to scan
     * @param bool $classEnabled Flag to enable or disable analysis for class references and orphans.
     * @param bool $bladeEnabled Flag to enable or disable analysis for Blade template references and orphans.
     * @param string $workingDir The working directory to resolve relative paths.
     *
     * @return AnalysisResult The result of the analysis, including a report of orphaned references and the number of files processed.
     */
    public function analyze(
        Configuration $config,
        array $paths,
        bool $classEnabled,
        bool $bladeEnabled,
        string $workingDir,
    ): AnalysisResult {
        $absolutePaths = array_map(
            fn (string $path): string => $this->toAbsolutePath($path, $workingDir),
            $paths,
        );

        /**
         * Find all PHP and related files in the given paths.
         */
        $finder = new Finder();
        $finder->files()->in($absolutePaths);
        foreach ($config->extensions as $ext) {
            $finder->name('*.'.$ext);
        }
        foreach ($config->excludePaths as $exclude) {
            $finder->notPath($exclude);
        }

        $parser = new FileParser();
        $classMapBuilder = new ClassMapBuilder();
        $referenceScanner = new ReferenceScanner();
        $bladeReferenceScanner = new BladeReferenceScanner();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($classMapBuilder);
        $traverser->addVisitor($referenceScanner);
        $traverser->addVisitor($bladeReferenceScanner);

        /**
         * Traverse all files and build the class map.
         */
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
            $traverser->traverse($stmts);
            $fileCount++;
        }

        /**
         * Scan extra blade reference paths (e.g. routes/) with a blade-only traverser
         * so that view() / Route::view() calls there are picked up without polluting the class map.
         */
        $bladeRefTraverser = new NodeTraverser();
        $bladeRefTraverser->addVisitor(new NameResolver());
        $bladeRefTraverser->addVisitor($bladeReferenceScanner);

        foreach ($config->bladeReferencePaths as $refPath) {
            $absRefPath = $this->toAbsolutePath($refPath, $workingDir);
            if (! is_dir($absRefPath)) {
                continue;
            }

            $refFinder = new Finder();
            $refFinder->files()->in($absRefPath);
            foreach ($config->extensions as $ext) {
                $refFinder->name('*.'.$ext);
            }

            foreach ($refFinder as $file) {
                $filePath = $file->getRealPath();
                if ($filePath === false) {
                    continue;
                }

                $stmts = $parser->parse($filePath);
                if ($stmts === []) {
                    continue;
                }

                $bladeRefTraverser->traverse($stmts);
            }
        }

        /**
         * Detect orphans based on the collected references.
         */

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
                    fn (string $path): string => $this->toAbsolutePath($path, $workingDir),
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

        return new AnalysisResult(
            report: new Report($classOrphans, $bladeOrphans),
            fileCount: $fileCount,
        );
    }

    private function toAbsolutePath(string $path, string $workingDir): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
            return $path;
        }

        return $workingDir.DIRECTORY_SEPARATOR.$path;
    }
}
