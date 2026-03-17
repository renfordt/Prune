<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Output;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Renfordt\Prune\Analyzer\ClassEntry;
use Renfordt\Prune\Blade\BladeEntry;
use Renfordt\Prune\Output\JsonFormatter;
use Renfordt\Prune\Output\Report;

#[CoversClass(JsonFormatter::class)]
#[UsesClass(ClassEntry::class)]
#[UsesClass(BladeEntry::class)]
#[UsesClass(Report::class)]
class JsonFormatterTest extends TestCase
{
    #[Test]
    public function itFormatsClassOrphansAsJson(): void
    {
        $report = new Report(
            classOrphans: [
                new ClassEntry('App\Orphan', 'src/Orphan.php', 10),
            ],
        );

        $formatter = new JsonFormatter();
        $result = $formatter->format($report);

        $decoded = json_decode($result, true);

        $this->assertCount(1, $decoded['classOrphans']);
        $this->assertSame('App\Orphan', $decoded['classOrphans'][0]['fqcn']);
        $this->assertSame('src/Orphan.php', $decoded['classOrphans'][0]['file']);
        $this->assertSame(10, $decoded['classOrphans'][0]['line']);
        $this->assertSame([], $decoded['bladeOrphans']);
    }

    #[Test]
    public function itFormatsBladeOrphansAsJson(): void
    {
        $report = new Report(
            bladeOrphans: [
                new BladeEntry('orphaned', 'resources/views/orphaned.blade.php'),
            ],
        );

        $formatter = new JsonFormatter();
        $result = $formatter->format($report);

        $decoded = json_decode($result, true);

        $this->assertSame([], $decoded['classOrphans']);
        $this->assertCount(1, $decoded['bladeOrphans']);
        $this->assertSame('orphaned', $decoded['bladeOrphans'][0]['viewName']);
        $this->assertSame('resources/views/orphaned.blade.php', $decoded['bladeOrphans'][0]['file']);
    }
}
