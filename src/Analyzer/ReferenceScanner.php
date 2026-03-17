<?php

declare(strict_types=1);

namespace Renfordt\Prune\Analyzer;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeVisitorAbstract;

class ReferenceScanner extends NodeVisitorAbstract
{
    /** @var array<string, true> */
    private array $references = [];

    public function setCurrentFile(string $file): void
    {
    }

    public function enterNode(Node $node): null
    {
        // extends / implements
        if ($node instanceof Class_) {
            if ($node->extends instanceof \PhpParser\Node\Name) {
                $this->addReference($node->extends);
            }
            foreach ($node->implements as $implement) {
                $this->addReference($implement);
            }
        }

        if ($node instanceof Interface_) {
            foreach ($node->extends as $extend) {
                $this->addReference($extend);
            }
        }

        if ($node instanceof Enum_) {
            foreach ($node->implements as $implement) {
                $this->addReference($implement);
            }
        }

        // trait use
        if ($node instanceof TraitUse) {
            foreach ($node->traits as $trait) {
                $this->addReference($trait);
            }
        }

        // new ClassName()
        if ($node instanceof New_ && $node->class instanceof Name) {
            $this->addReference($node->class);
        }

        // ClassName::method(), ClassName::$prop, ClassName::CONST
        if ($node instanceof StaticCall && $node->class instanceof Name) {
            $this->addReference($node->class);
        }
        if ($node instanceof StaticPropertyFetch && $node->class instanceof Name) {
            $this->addReference($node->class);
        }
        if ($node instanceof ClassConstFetch && $node->class instanceof Name) {
            $this->addReference($node->class);
        }

        // instanceof
        if ($node instanceof Instanceof_ && $node->class instanceof Name) {
            $this->addReference($node->class);
        }

        // catch
        if ($node instanceof Catch_) {
            foreach ($node->types as $type) {
                $this->addReference($type);
            }
        }

        // Type hints (parameters, return types, properties)
        if ($node instanceof Node\Param && $node->type instanceof \PhpParser\Node) {
            $this->collectTypeReferences($node->type);
        }
        if (($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) && $node->returnType instanceof \PhpParser\Node) {
            $this->collectTypeReferences($node->returnType);
        }
        if ($node instanceof Node\Stmt\Property && $node->type instanceof \PhpParser\Node) {
            $this->collectTypeReferences($node->type);
        }

        // Attributes
        if ($node instanceof Node\AttributeGroup) {
            foreach ($node->attrs as $attr) {
                $this->addReference($attr->name);
            }
        }

        return null;
    }

    private function collectTypeReferences(Node $type): void
    {
        if ($type instanceof Name) {
            $this->addReference($type);
        } elseif ($type instanceof Node\NullableType && $type->type instanceof Name) {
            $this->addReference($type->type);
        } elseif ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
            foreach ($type->types as $subType) {
                $this->collectTypeReferences($subType);
            }
        }
    }

    private function addReference(Name $name): void
    {
        $resolved = $name->toString();

        // Skip built-in types and self-references
        if (in_array(strtolower($resolved), ['self', 'static', 'parent', 'int', 'string', 'float', 'bool', 'array', 'object', 'mixed', 'void', 'never', 'null', 'true', 'false', 'iterable', 'callable'], true)) {
            return;
        }

        $this->references[$resolved] = true;
    }

    /**
     * @return list<string>
     */
    public function getReferences(): array
    {
        return array_keys($this->references);
    }

    public function reset(): void
    {
        $this->references = [];
    }
}
