<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Renfordt\Prune\Config\Configuration;

#[CoversClass(Configuration::class)]
class ConfigurationTest extends TestCase
{
    #[Test]
    public function itHasDefaultValues(): void
    {
        $config = new Configuration();

        $this->assertSame(['src'], $config->paths);
        $this->assertSame(['vendor'], $config->excludePaths);
        $this->assertSame(['php'], $config->extensions);
        $this->assertSame('console', $config->format);
        $this->assertTrue($config->bladeEnabled);
        $this->assertSame(['resources/views'], $config->bladeViewPaths);
        $this->assertSame(['routes'], $config->bladeReferencePaths);
        $this->assertSame([], $config->bladeExcludeViews);
    }

    #[Test]
    public function itLoadsExtensionsFromConfig(): void
    {
        $fixture = __DIR__ . '/../Fixtures/config/prune-extensions.neon';
        $config = Configuration::load(dirname($fixture), $fixture);

        $this->assertSame(['php', 'inc'], $config->extensions);
    }

    #[Test]
    public function itReturnsDefaultsWhenConfigFileNotFound(): void
    {
        $config = Configuration::load('/nonexistent/path', '/nonexistent/prune.neon');

        $this->assertSame(['src'], $config->paths);
        $this->assertSame(['php'], $config->extensions);
    }

    #[Test]
    public function itAutoDiscoversPruneNeon(): void
    {
        // The fixture dir has no prune.neon so defaults are returned
        $config = Configuration::load(__DIR__ . '/../Fixtures');

        $this->assertSame(['src'], $config->paths);
    }
}
