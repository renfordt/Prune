<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Analyzer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Renfordt\Prune\Analyzer\AnalysisResult;
use Renfordt\Prune\Analyzer\ClassEntry;
use Renfordt\Prune\Analyzer\ClassMapBuilder;
use Renfordt\Prune\Analyzer\OrphanDetector;
use Renfordt\Prune\Analyzer\PruneAnalyzer;
use Renfordt\Prune\Analyzer\ReferenceScanner;
use Renfordt\Prune\Blade\BladeDirectiveScanner;
use Renfordt\Prune\Blade\BladeEntry;
use Renfordt\Prune\Blade\BladeOrphanDetector;
use Renfordt\Prune\Blade\BladeReferenceScanner;
use Renfordt\Prune\Blade\BladeViewDiscovery;
use Renfordt\Prune\Config\Configuration;
use Renfordt\Prune\Output\Report;
use Renfordt\Prune\Parser\FileParser;

#[CoversClass(PruneAnalyzer::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ClassEntry::class)]
#[UsesClass(ClassMapBuilder::class)]
#[UsesClass(OrphanDetector::class)]
#[UsesClass(ReferenceScanner::class)]
#[UsesClass(BladeDirectiveScanner::class)]
#[UsesClass(BladeEntry::class)]
#[UsesClass(BladeOrphanDetector::class)]
#[UsesClass(BladeReferenceScanner::class)]
#[UsesClass(BladeViewDiscovery::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(Report::class)]
#[UsesClass(FileParser::class)]
class PruneAnalyzerTest extends TestCase
{
    private string $pipelineDir;

    private string $bladeViewsDir;

    private string $bladeRouteDir;

    protected function setUp(): void
    {
        $this->pipelineDir = __DIR__ . '/../Fixtures/pipeline';
        $this->bladeViewsDir = __DIR__ . '/../Fixtures/blade/views';
        $this->bladeRouteDir = __DIR__ . '/../Fixtures/blade-route';
    }

    #[Test]
    public function itDetectsOrphanedClasses(): void
    {
        $config = new Configuration(
            paths: [$this->pipelineDir],
            bladeEnabled: false,
        );

        $result = new PruneAnalyzer()->analyze(
            config: $config,
            paths: [$this->pipelineDir],
            classEnabled: true,
            bladeEnabled: false,
            workingDir: $this->pipelineDir,
        );

        $orphanFqcns = array_map(fn (ClassEntry $e): string => $e->fqcn, $result->report->classOrphans);
        $this->assertContains(\Renfordt\Prune\Tests\Fixtures\Pipeline\TrulyOrphanedClass::class, $orphanFqcns);
        $this->assertNotContains(\Renfordt\Prune\Tests\Fixtures\Pipeline\ReferencedClass::class, $orphanFqcns);
    }

    #[Test]
    public function itReportsFileCount(): void
    {
        $config = new Configuration(
            paths: [$this->pipelineDir],
            bladeEnabled: false,
        );

        $result = new PruneAnalyzer()->analyze(
            config: $config,
            paths: [$this->pipelineDir],
            classEnabled: true,
            bladeEnabled: false,
            workingDir: $this->pipelineDir,
        );

        $this->assertSame(3, $result->fileCount);
    }

    #[Test]
    public function itSkipsClassAnalysisWhenDisabled(): void
    {
        $config = new Configuration(
            paths: [$this->pipelineDir],
            bladeEnabled: false,
        );

        $result = new PruneAnalyzer()->analyze(
            config: $config,
            paths: [$this->pipelineDir],
            classEnabled: false,
            bladeEnabled: false,
            workingDir: $this->pipelineDir,
        );

        $this->assertSame([], $result->report->classOrphans);
    }

    #[Test]
    public function itDetectsOrphanedBladeViews(): void
    {
        $config = new Configuration(
            paths: [$this->bladeViewsDir],
            bladeEnabled: true,
            bladeViewPaths: [$this->bladeViewsDir],
        );

        $result = new PruneAnalyzer()->analyze(
            config: $config,
            paths: [$this->bladeViewsDir],
            classEnabled: false,
            bladeEnabled: true,
            workingDir: $this->bladeViewsDir,
        );

        $orphanNames = array_map(fn (BladeEntry $e): string => $e->viewName, $result->report->bladeOrphans);
        $this->assertContains('orphaned', $orphanNames);
    }

    #[Test]
    public function itPicksUpViewReferencesFromBladeReferencePaths(): void
    {
        $viewsDir = $this->bladeRouteDir . '/views';
        $routesDir = $this->bladeRouteDir . '/routes';

        $config = new Configuration(
            paths: [$viewsDir],
            bladeEnabled: true,
            bladeViewPaths: [$viewsDir],
            bladeReferencePaths: [$routesDir],
        );

        $result = new PruneAnalyzer()->analyze(
            config: $config,
            paths: [$viewsDir],
            classEnabled: false,
            bladeEnabled: true,
            workingDir: $this->bladeRouteDir,
        );

        $orphanNames = array_map(fn (BladeEntry $e): string => $e->viewName, $result->report->bladeOrphans);
        // 'about' is referenced via Route::view() in routes/web.php — must NOT be orphaned
        $this->assertNotContains('about', $orphanNames);
        // 'orphaned' has no reference anywhere — must be orphaned
        $this->assertContains('orphaned', $orphanNames);
    }
}
