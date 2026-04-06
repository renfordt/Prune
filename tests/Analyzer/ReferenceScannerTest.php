<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Analyzer;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Renfordt\Prune\Analyzer\ReferenceScanner;
use Renfordt\Prune\Parser\FileParser;

#[CoversClass(ReferenceScanner::class)]
#[UsesClass(FileParser::class)]
class ReferenceScannerTest extends TestCase
{
    #[Test]
    public function itFindsNewInstantiations(): void
    {
        $references = $this->scanFixture('ConsumerClass.php');

        $this->assertContains(\Renfordt\Prune\Tests\Fixtures\UsedClass::class, $references);
    }

    #[Test]
    public function itFindsImplementsReferences(): void
    {
        $references = $this->scanFixture('InterfaceUser.php');

        $this->assertContains(\Renfordt\Prune\Tests\Fixtures\Greeter::class, $references);
    }

    // --- PHPDoc detection ---

    #[Test]
    public function itFindsDocBlockParamReferencesViaFqcn(): void
    {
        $references = $this->scanFixture('DocBlockConsumer.php');

        $this->assertContains(\Renfordt\Prune\Tests\Fixtures\OrphanedClass::class, $references);
    }

    #[Test]
    public function itFindsDocBlockVarAndReturnReferencesViaUseMap(): void
    {
        $references = $this->scanFixture('DocBlockConsumer.php');

        $this->assertContains(\Renfordt\Prune\Tests\Fixtures\UsedClass::class, $references);
    }

    #[Test]
    public function itFindsDocBlockThrowsReferencesViaFqcn(): void
    {
        // @throws \Renfordt\Prune\Tests\Fixtures\UsedClass
        $references = $this->scanFixture('DocBlockConsumer.php');

        $this->assertContains(\Renfordt\Prune\Tests\Fixtures\UsedClass::class, $references);
    }

    #[Test]
    public function itFindsFqcnInDocBlockGenerics(): void
    {
        $references = $this->scanCode(<<<'PHP'
            <?php
            class Foo {
                /** @return \App\Models\Collection<\App\Models\User> */
                public function items(): array { return []; }
            }
            PHP);

        $this->assertContains('App\Models\Collection', $references);
        $this->assertContains('App\Models\User', $references);
    }

    // --- FuncCall detection (::class syntax, covered by fixture) ---

    #[Test]
    public function itFindsClassExistsWithClassConst(): void
    {
        $references = $this->scanFixture('FuncCallConsumer.php');

        $this->assertContains(\Renfordt\Prune\Tests\Fixtures\UsedClass::class, $references);
    }

    #[Test]
    public function itFindsClassExistsWithStringLiteral(): void
    {
        $references = $this->scanCode(<<<'PHP'
            <?php
            class_exists('App\Services\SomeService');
            PHP);

        $this->assertContains('App\Services\SomeService', $references);
    }

    #[Test]
    public function itFindsIsAWithStringLiteral(): void
    {
        $references = $this->scanCode(<<<'PHP'
            <?php
            $result = is_a($obj, 'App\Models\User');
            PHP);

        $this->assertContains('App\Models\User', $references);
    }

    #[Test]
    public function itFindsIsSubclassOfWithStringLiteral(): void
    {
        $references = $this->scanCode(<<<'PHP'
            <?php
            $result = is_subclass_of($obj, 'App\Models\BaseModel');
            PHP);

        $this->assertContains('App\Models\BaseModel', $references);
    }

    #[Test]
    public function itIgnoresShortNameStringLiterals(): void
    {
        // Short names (no backslash) are ambiguous and intentionally not tracked
        $references = $this->scanCode(<<<'PHP'
            <?php
            class_exists('User');
            PHP);

        $this->assertNotContains('User', $references);
    }

    #[Test]
    public function itFindsClassImplementsWithClassConst(): void
    {
        $references = $this->scanFixture('FuncCallConsumer.php');

        $this->assertContains(\Renfordt\Prune\Tests\Fixtures\UsedClass::class, $references);
    }

    #[Test]
    public function itFindsClassParentsWithClassConst(): void
    {
        $references = $this->scanFixture('FuncCallConsumer.php');

        $this->assertContains(\Renfordt\Prune\Tests\Fixtures\OrphanedClass::class, $references);
    }

    #[Test]
    public function itFindsAppContainerWithClassConst(): void
    {
        $references = $this->scanFixture('FuncCallConsumer.php');

        // app(\Renfordt\Prune\Tests\Fixtures\UsedClass::class) — after Rector refactoring
        $this->assertContains(\Renfordt\Prune\Tests\Fixtures\UsedClass::class, $references);
    }

    #[Test]
    public function itFindsAppContainerWithStringLiteral(): void
    {
        $references = $this->scanCode(<<<'PHP'
            <?php
            app('App\Services\PaymentService');
            PHP);

        $this->assertContains('App\Services\PaymentService', $references);
    }

    // --- Route string controller references ---

    #[Test]
    public function itFindsRouteStringControllerFqcn(): void
    {
        $references = $this->scanCode(<<<'PHP'
            <?php
            use Illuminate\Support\Facades\Route;
            Route::get('/users', 'App\Http\Controllers\UserController@index');
            PHP);

        $this->assertContains('App\Http\Controllers\UserController', $references);
    }

    #[Test]
    public function itFindsRouteStringControllerForAllHttpMethods(): void
    {
        $references = $this->scanCode(<<<'PHP'
            <?php
            use Illuminate\Support\Facades\Route;
            Route::post('/users', 'App\Http\Controllers\UserController@store');
            Route::put('/users/{id}', 'App\Http\Controllers\UserController@update');
            PHP);

        $this->assertContains('App\Http\Controllers\UserController', $references);
    }

    #[Test]
    public function itIgnoresRouteStringControllerWithoutNamespace(): void
    {
        // Short names without backslash are ambiguous — intentionally skipped
        $references = $this->scanCode(<<<'PHP'
            <?php
            use Illuminate\Support\Facades\Route;
            Route::get('/users', 'UserController@index');
            PHP);

        $this->assertNotContains('UserController', $references);
    }

    /**
     * @return list<string>
     */
    private function scanFixture(string $filename): array
    {
        $parser = new FileParser();
        $scanner = new ReferenceScanner();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($scanner);

        $fixture = __DIR__ . '/../Fixtures/' . $filename;
        $traverser->traverse($parser->parse($fixture));

        return $scanner->getReferences();
    }

    /**
     * @return list<string>
     */
    private function scanCode(string $code): array
    {
        $parser = new ParserFactory()->createForNewestSupportedVersion();
        $scanner = new ReferenceScanner();

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
