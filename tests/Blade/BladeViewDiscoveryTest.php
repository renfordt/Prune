<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Blade;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Renfordt\Prune\Blade\BladeEntry;
use Renfordt\Prune\Blade\BladeViewDiscovery;

#[CoversClass(BladeViewDiscovery::class)]
#[UsesClass(BladeEntry::class)]
class BladeViewDiscoveryTest extends TestCase
{
    #[Test]
    public function itDiscoversBladeViews(): void
    {
        $discovery = new BladeViewDiscovery();
        $views = $discovery->discover([__DIR__ . '/../Fixtures/blade/views']);

        $viewNames = array_map(fn (BladeEntry $e): string => $e->viewName, $views);

        $this->assertContains('welcome', $viewNames);
        $this->assertContains('orphaned', $viewNames);
        $this->assertContains('layouts.app', $viewNames);
        $this->assertContains('components.alert', $viewNames);
    }

    #[Test]
    public function itReturnsEmptyForNonExistentPath(): void
    {
        $discovery = new BladeViewDiscovery();
        $views = $discovery->discover(['/tmp/nonexistent-blade-path']);

        $this->assertSame([], $views);
    }

    #[Test]
    public function itConvertsPathsToDotNotation(): void
    {
        $discovery = new BladeViewDiscovery();
        $views = $discovery->discover([__DIR__ . '/../Fixtures/blade/views']);
        $layoutView = array_find($views, fn ($view): bool => $view->viewName === 'layouts.app');

        $this->assertNotNull($layoutView);
        $this->assertStringEndsWith('layouts/app.blade.php', $layoutView->file);
    }
}
