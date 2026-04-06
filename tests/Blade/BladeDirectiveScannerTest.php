<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Blade;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Renfordt\Prune\Blade\BladeDirectiveScanner;

#[CoversClass(BladeDirectiveScanner::class)]
class BladeDirectiveScannerTest extends TestCase
{
    private BladeDirectiveScanner $scanner;

    protected function setUp(): void
    {
        $this->scanner = new BladeDirectiveScanner();
    }

    #[Test]
    public function itExtractsIncludeDirectives(): void
    {
        $content = "@include('partials.header')";

        $references = $this->scanner->scan($content);

        $this->assertContains('partials.header', $references);
    }

    #[Test]
    public function itExtractsIncludeIfDirectives(): void
    {
        $content = "@includeIf('partials.sidebar')";

        $references = $this->scanner->scan($content);

        $this->assertContains('partials.sidebar', $references);
    }

    #[Test]
    public function itExtractsIncludeWhenDirectives(): void
    {
        $content = "@includeWhen(\$condition, 'partials.banner')";

        $references = $this->scanner->scan($content);

        $this->assertContains('partials.banner', $references);
    }

    #[Test]
    public function itExtractsIncludeUnlessDirectives(): void
    {
        $content = "@includeUnless(\$condition, 'partials.footer')";

        $references = $this->scanner->scan($content);

        $this->assertContains('partials.footer', $references);
    }

    #[Test]
    public function itExtractsIncludeFirstDirectives(): void
    {
        $content = "@includeFirst('custom.header')";

        $references = $this->scanner->scan($content);

        $this->assertContains('custom.header', $references);
    }

    #[Test]
    public function itExtractsExtendsDirectives(): void
    {
        $content = "@extends('layouts.app')";

        $references = $this->scanner->scan($content);

        $this->assertContains('layouts.app', $references);
    }

    #[Test]
    public function itExtractsComponentDirectives(): void
    {
        $content = "@component('components.alert')";

        $references = $this->scanner->scan($content);

        $this->assertContains('components.alert', $references);
    }

    #[Test]
    public function itExtractsEachDirectives(): void
    {
        $content = "@each('partials.item', \$items, 'item')";

        $references = $this->scanner->scan($content);

        $this->assertContains('partials.item', $references);
    }

    #[Test]
    public function itExtractsComponentTags(): void
    {
        $content = '<x-alert />';

        $references = $this->scanner->scan($content);

        $this->assertContains('components.alert', $references);
    }

    #[Test]
    public function itExtractsNestedComponentTags(): void
    {
        $content = '<x-forms.input />';

        $references = $this->scanner->scan($content);

        $this->assertContains('components.forms.input', $references);
    }

    #[Test]
    public function itExtractsLivewireDirectives(): void
    {
        $content = "@livewire('counter')";

        $references = $this->scanner->scan($content);

        $this->assertContains('counter', $references);
    }

    #[Test]
    public function itExtractsLivewireTags(): void
    {
        $content = '<livewire:counter />';

        $references = $this->scanner->scan($content);

        $this->assertContains('counter', $references);
    }

    #[Test]
    public function itExtractsKebabCaseLivewireTags(): void
    {
        $content = '<livewire:user-profile />';

        $references = $this->scanner->scan($content);

        $this->assertContains('user-profile', $references);
    }

    #[Test]
    public function itExtractsKebabCaseComponentTags(): void
    {
        $content = '<x-alert-box />';

        $references = $this->scanner->scan($content);

        $this->assertContains('components.alert-box', $references);
    }

    #[Test]
    public function itReturnsEmptyForNoDirectives(): void
    {
        $content = '<div>Hello world</div>';

        $references = $this->scanner->scan($content);

        $this->assertSame([], $references);
    }

    #[Test]
    public function itDeduplicatesReferences(): void
    {
        $content = "@include('partials.header')\n@include('partials.header')";

        $references = $this->scanner->scan($content);

        $this->assertCount(1, $references);
        $this->assertContains('partials.header', $references);
    }

    #[Test]
    public function itHandlesDoubleQuotedDirectives(): void
    {
        $content = '@include("partials.header")';

        $references = $this->scanner->scan($content);

        $this->assertContains('partials.header', $references);
    }
}
