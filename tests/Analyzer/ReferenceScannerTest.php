<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Analyzer;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
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
        $parser = new FileParser();
        $scanner = new ReferenceScanner();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($scanner);

        $fixture = __DIR__ . '/../Fixtures/ConsumerClass.php';
        $scanner->setCurrentFile($fixture);
        $traverser->traverse($parser->parse($fixture));

        $references = $scanner->getReferences();

        $this->assertContains(\Renfordt\Prune\Tests\Fixtures\UsedClass::class, $references);
    }

    #[Test]
    public function itFindsImplementsReferences(): void
    {
        $parser = new FileParser();
        $scanner = new ReferenceScanner();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($scanner);

        $fixture = __DIR__ . '/../Fixtures/InterfaceUser.php';
        $scanner->setCurrentFile($fixture);
        $traverser->traverse($parser->parse($fixture));

        $references = $scanner->getReferences();

        $this->assertContains(\Renfordt\Prune\Tests\Fixtures\Greeter::class, $references);
    }
}
