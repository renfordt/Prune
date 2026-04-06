<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Blade;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Renfordt\Prune\Blade\BladeReferenceScanner;

#[CoversClass(BladeReferenceScanner::class)]
class BladeReferenceScannerTest extends TestCase
{
    #[Test]
    public function itExtractsViewFunctionCalls(): void
    {
        $code = <<<'PHP'
        <?php
        return view('welcome');
        PHP;

        $references = $this->scanCode($code);

        $this->assertContains('welcome', $references);
    }

    #[Test]
    public function itExtractsViewMakeCalls(): void
    {
        $code = <<<'PHP'
        <?php
        use Illuminate\Support\Facades\View;
        View::make('layouts.app');
        PHP;

        $references = $this->scanCode($code);

        $this->assertContains('layouts.app', $references);
    }

    #[Test]
    public function itExtractsViewFirstCalls(): void
    {
        $code = <<<'PHP'
        <?php
        use Illuminate\Support\Facades\View;
        View::first(['custom.page', 'default.page']);
        PHP;

        $references = $this->scanCode($code);

        $this->assertContains('custom.page', $references);
        $this->assertContains('default.page', $references);
    }

    #[Test]
    public function itExtractsRouteViewCalls(): void
    {
        $code = <<<'PHP'
        <?php
        use Illuminate\Support\Facades\Route;
        Route::view('/about', 'pages.about');
        PHP;

        $references = $this->scanCode($code);

        $this->assertContains('pages.about', $references);
    }

    #[Test]
    public function itExtractsThisViewCalls(): void
    {
        $code = <<<'PHP'
        <?php
        class Mail {
            public function build() {
                return $this->view('emails.welcome');
            }
        }
        PHP;

        $references = $this->scanCode($code);

        $this->assertContains('emails.welcome', $references);
    }

    #[Test]
    public function itExtractsThisMarkdownCalls(): void
    {
        $code = <<<'PHP'
        <?php
        class Mail {
            public function build() {
                return $this->markdown('emails.order');
            }
        }
        PHP;

        $references = $this->scanCode($code);

        $this->assertContains('emails.order', $references);
    }

    #[Test]
    public function itExtractsResponseViewCalls(): void
    {
        $code = <<<'PHP'
        <?php
        return response()->view('errors.404', [], 404);
        PHP;

        $references = $this->scanCode($code);

        $this->assertContains('errors.404', $references);
    }

    #[Test]
    public function itExtractsChainedViewCalls(): void
    {
        $code = <<<'PHP'
        <?php
        class Controller {
            public function show() {
                return $this->someHelper()->view('pages.show');
            }
        }
        PHP;

        $references = $this->scanCode($code);

        $this->assertContains('pages.show', $references);
    }

    #[Test]
    public function itReturnsEmptyForNoViewReferences(): void
    {
        $code = <<<'PHP'
        <?php
        echo "hello";
        PHP;

        $references = $this->scanCode($code);

        $this->assertSame([], $references);
    }

    /**
     * @return list<string>
     */
    private function scanCode(string $code): array
    {
        $parser = new ParserFactory()->createForNewestSupportedVersion();
        $scanner = new BladeReferenceScanner();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($scanner);

        $stmts = $parser->parse($code);
        if ($stmts === null) {
            return [];
        }

        $traverser->traverse($stmts);

        return $scanner->getReferences();
    }
}
