<?php

declare(strict_types=1);

namespace Renfordt\Prune\Analyzer;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;

class ClassMapBuilder extends NodeVisitorAbstract
{
    /** @var array<string, ClassEntry> */
    private array $classMap = [];

    private string $currentFile = '';

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function enterNode(Node $node): null
    {
        if (! $node instanceof Class_ && ! $node instanceof Interface_ && ! $node instanceof Trait_ && ! $node instanceof Enum_) {
            return null;
        }

        if ($node instanceof Class_ && $node->isAnonymous()) {
            return null;
        }

        $name = $node->namespacedName?->toString();
        if ($name === null) {
            return null;
        }

        $this->classMap[$name] = new ClassEntry(
            fqcn: $name,
            file: $this->currentFile,
            line: $node->getStartLine(),
        );

        return null;
    }

    /**
     * @return array<string, ClassEntry>
     */
    public function getClassMap(): array
    {
        return $this->classMap;
    }

}
