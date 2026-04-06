<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Output;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Renfordt\Prune\Analyzer\ClassEntry;
use Renfordt\Prune\Blade\BladeEntry;
use Renfordt\Prune\Output\HtmlFormatter;
use Renfordt\Prune\Output\Report;

#[CoversClass(HtmlFormatter::class)]
#[UsesClass(ClassEntry::class)]
#[UsesClass(BladeEntry::class)]
#[UsesClass(Report::class)]
class HtmlFormatterTest extends TestCase
{
    #[Test]
    public function itRendersClassSectionWhenOrphansExist(): void
    {
        $report = new Report(
            classOrphans: [new ClassEntry('App\Orphan', 'src/Orphan.php', 5)],
        );

        $html = new HtmlFormatter()->format($report);

        $this->assertStringContainsString('<h2>Classes</h2>', $html);
        $this->assertStringContainsString('App\Orphan', $html);
    }

    #[Test]
    public function itDoesNotRenderClassSectionWhenEmpty(): void
    {
        $report = new Report(classOrphans: []);

        $html = new HtmlFormatter()->format($report);

        $this->assertStringNotContainsString('<h2>Classes</h2>', $html);
    }

    #[Test]
    public function itRendersBladeSectionWhenOrphansExist(): void
    {
        $report = new Report(
            bladeOrphans: [new BladeEntry('pages.orphan', 'resources/views/orphan.blade.php')],
        );

        $html = new HtmlFormatter()->format($report);

        $this->assertStringContainsString('<h2>Blade Views</h2>', $html);
        $this->assertStringContainsString('pages.orphan', $html);
    }

    #[Test]
    public function itDoesNotRenderBladeSectionWhenEmpty(): void
    {
        $report = new Report(bladeOrphans: []);

        $html = new HtmlFormatter()->format($report);

        $this->assertStringNotContainsString('<h2>Blade Views</h2>', $html);
    }

    #[Test]
    public function itEscapesHtmlSpecialCharsInClassNames(): void
    {
        $report = new Report(
            classOrphans: [new ClassEntry('App\<Script>', 'src/file.php', 1)],
        );

        $html = new HtmlFormatter()->format($report);

        $this->assertStringNotContainsString('<Script>', $html);
        $this->assertStringContainsString('&lt;Script&gt;', $html);
    }
}
