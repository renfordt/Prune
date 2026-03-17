<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Analyzer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Renfordt\Prune\Analyzer\ClassEntry;
use Renfordt\Prune\Analyzer\OrphanDetector;

#[CoversClass(OrphanDetector::class)]
#[UsesClass(ClassEntry::class)]
class OrphanDetectorTest extends TestCase
{
    #[Test]
    public function itDetectsOrphanedClasses(): void
    {
        $classMap = [
            'App\UsedClass' => new ClassEntry('App\UsedClass', 'src/UsedClass.php', 5),
            'App\OrphanedClass' => new ClassEntry('App\OrphanedClass', 'src/OrphanedClass.php', 3),
        ];

        $references = ['App\UsedClass'];

        $detector = new OrphanDetector();
        $orphans = $detector->detect($classMap, $references);

        $this->assertCount(1, $orphans);
        $this->assertSame('App\OrphanedClass', $orphans[0]->fqcn);
    }

    #[Test]
    public function itReturnsEmptyWhenNoOrphans(): void
    {
        $classMap = [
            'App\UsedClass' => new ClassEntry('App\UsedClass', 'src/UsedClass.php', 5),
        ];

        $references = ['App\UsedClass'];

        $detector = new OrphanDetector();
        $orphans = $detector->detect($classMap, $references);

        $this->assertCount(0, $orphans);
    }
}
