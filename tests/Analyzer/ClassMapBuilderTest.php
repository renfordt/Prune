<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Analyzer;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Renfordt\Prune\Analyzer\ClassEntry;
use Renfordt\Prune\Analyzer\ClassMapBuilder;
use Renfordt\Prune\Parser\FileParser;

#[CoversClass(ClassMapBuilder::class)]
#[UsesClass(ClassEntry::class)]
#[UsesClass(FileParser::class)]
class ClassMapBuilderTest extends TestCase
{
    #[Test]
    public function itFindsClassDeclarations(): void
    {
        $parser = new FileParser();
        $builder = new ClassMapBuilder();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($builder);

        $fixture = __DIR__ . '/../Fixtures/UsedClass.php';
        $builder->setCurrentFile($fixture);
        $traverser->traverse($parser->parse($fixture));

        $classMap = $builder->getClassMap();

        $this->assertArrayHasKey(\Renfordt\Prune\Tests\Fixtures\UsedClass::class, $classMap);
        $this->assertSame($fixture, $classMap[\Renfordt\Prune\Tests\Fixtures\UsedClass::class]->file);
    }

    #[Test]
    public function itFindsInterfacesAndClasses(): void
    {
        $parser = new FileParser();
        $builder = new ClassMapBuilder();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($builder);

        $fixture = __DIR__ . '/../Fixtures/InterfaceUser.php';
        $builder->setCurrentFile($fixture);
        $traverser->traverse($parser->parse($fixture));

        $classMap = $builder->getClassMap();

        $this->assertArrayHasKey(\Renfordt\Prune\Tests\Fixtures\Greeter::class, $classMap);
        $this->assertArrayHasKey(\Renfordt\Prune\Tests\Fixtures\InterfaceUser::class, $classMap);
    }
}
