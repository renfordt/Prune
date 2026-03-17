<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Blade;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Renfordt\Prune\Blade\BladeEntry;
use Renfordt\Prune\Blade\BladeOrphanDetector;

#[CoversClass(BladeOrphanDetector::class)]
#[UsesClass(BladeEntry::class)]
class BladeOrphanDetectorTest extends TestCase
{
    #[Test]
    public function itDetectsOrphanedBladeViews(): void
    {
        $views = [
            new BladeEntry('welcome', '/views/welcome.blade.php'),
            new BladeEntry('orphaned', '/views/orphaned.blade.php'),
        ];

        $references = ['welcome'];

        $detector = new BladeOrphanDetector();
        $orphans = $detector->detect($views, $references);

        $this->assertCount(1, $orphans);
        $this->assertSame('orphaned', $orphans[0]->viewName);
    }

    #[Test]
    public function itReturnsEmptyWhenNoOrphans(): void
    {
        $views = [
            new BladeEntry('welcome', '/views/welcome.blade.php'),
        ];

        $references = ['welcome'];

        $detector = new BladeOrphanDetector();
        $orphans = $detector->detect($views, $references);

        $this->assertSame([], $orphans);
    }

    #[Test]
    public function itExcludesConfiguredViews(): void
    {
        $views = [
            new BladeEntry('welcome', '/views/welcome.blade.php'),
            new BladeEntry('errors.404', '/views/errors/404.blade.php'),
        ];

        $references = [];
        $excludes = ['errors.404'];

        $detector = new BladeOrphanDetector();
        $orphans = $detector->detect($views, $references, $excludes);

        $this->assertCount(1, $orphans);
        $this->assertSame('welcome', $orphans[0]->viewName);
    }

    #[Test]
    public function itMatchesComponentReferences(): void
    {
        $views = [
            new BladeEntry('components.alert', '/views/components/alert.blade.php'),
        ];

        $references = ['components.alert'];

        $detector = new BladeOrphanDetector();
        $orphans = $detector->detect($views, $references);

        $this->assertSame([], $orphans);
    }

    #[Test]
    public function itSortsOrphansByViewName(): void
    {
        $views = [
            new BladeEntry('zebra', '/views/zebra.blade.php'),
            new BladeEntry('alpha', '/views/alpha.blade.php'),
            new BladeEntry('middle', '/views/middle.blade.php'),
        ];

        $detector = new BladeOrphanDetector();
        $orphans = $detector->detect($views, []);

        $this->assertSame('alpha', $orphans[0]->viewName);
        $this->assertSame('middle', $orphans[1]->viewName);
        $this->assertSame('zebra', $orphans[2]->viewName);
    }
}
