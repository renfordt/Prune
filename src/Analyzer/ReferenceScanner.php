<?php

declare(strict_types=1);

namespace Renfordt\Prune\Analyzer;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

class ReferenceScanner extends NodeVisitorAbstract
{
    private const array BUILTIN_TYPES = [
        'self', 'static', 'parent', 'int', 'string', 'float', 'bool',
        'array', 'object', 'mixed', 'void', 'never', 'null', 'true', 'false',
        'iterable', 'callable',
    ];

    private const array FUNCALL_CLASS_FUNCTIONS = [
        'class_exists' => 0,
        'class_implements' => 0,
        'class_parents' => 0,
        'interface_exists' => 0,
        'is_a' => 1,
        'is_subclass_of' => 1,
        'app' => 0,
    ];

    /** @var array<string, true> */
    private array $references = [];

    /** @var array<string, string> Alias => FQCN, built from use statements per file */
    private array $useMap = [];

    /**
     * @param  array<Node>  $nodes
     * @return array<Node>|null
     */
    public function beforeTraverse(array $nodes): ?array
    {
        $this->useMap = [];

        return null;
    }

    public function enterNode(Node $node): null
    {
        // Track use imports for docblock resolution
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $alias = $use->alias?->toString() ?? $use->name->getLast();
                $this->useMap[$alias] = $use->name->toString();
            }
        }

        // extends / implements
        if ($node instanceof Class_) {
            if ($node->extends instanceof Name) {
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
        if ($node instanceof Node\Param && $node->type instanceof Node) {
            $this->collectTypeReferences($node->type);
        }
        if (($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) && $node->returnType instanceof Node) {
            $this->collectTypeReferences($node->returnType);
        }
        if ($node instanceof Node\Stmt\Property && $node->type instanceof Node) {
            $this->collectTypeReferences($node->type);
        }

        // Attributes
        if ($node instanceof Node\AttributeGroup) {
            foreach ($node->attrs as $attr) {
                $this->addReference($attr->name);
            }
        }

        // Function calls that reference classes: class_exists(), is_a(), app(), etc.
        if ($node instanceof FuncCall && $node->name instanceof Name) {
            $funcName = $node->name->toString();
            if (isset(self::FUNCALL_CLASS_FUNCTIONS[$funcName])) {
                $this->extractClassFromArg($node->getArgs(), self::FUNCALL_CLASS_FUNCTIONS[$funcName]);
            }
        }

        // PHPDoc comments on any node that can carry a docblock
        $this->scanDocComment($node);

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

    /**
     * @param  array<Node\Arg>  $args
     */
    private function extractClassFromArg(array $args, int $index): void
    {
        if (! isset($args[$index])) {
            return;
        }

        $value = $args[$index]->value;

        // String literal: class_exists('App\Models\User')
        // Only match FQCNs (containing backslash) to avoid ambiguous short names
        if ($value instanceof String_) {
            $className = ltrim($value->value, '\\');
            if ($className !== '' && str_contains($className, '\\')) {
                $this->references[$className] = true;
            }

            return;
        }

        // Class constant: class_exists(User::class)
        if ($value instanceof ClassConstFetch
            && $value->class instanceof Name
            && $value->name instanceof Node\Identifier
            && strtolower($value->name->toString()) === 'class') {
            $this->addReference($value->class);
        }
    }

    private function scanDocComment(Node $node): void
    {
        $docComment = $node->getDocComment();
        if (!$docComment instanceof \PhpParser\Comment\Doc) {
            return;
        }

        $text = $docComment->getText();

        // Extract types from @param, @return, @var, @throws, @template extends/of,
        // @extends, @implements, @mixin, @see tags
        preg_match_all(
            '/@(?:param|return|var|throws|extends|implements|mixin|see|template\s+\w+\s+(?:of|extends))\s+((?:\?|list<|array<|iterable<)?\\\\?[A-Z][a-zA-Z0-9_\\\\]*)/',
            $text,
            $matches,
        );

        foreach ($matches[1] as $raw) {
            // Strip leading ? or generic wrappers like list<, array<
            $raw = preg_replace('/^[?]|^(?:list|array|iterable)</', '', $raw) ?? $raw;
            $raw = rtrim($raw, '>');
            $name = ltrim($raw, '\\');

            if ($name === '') {
                continue;
            }

            // FQCN in docblock (contains backslash) — add directly
            if (str_contains($name, '\\')) {
                $this->addReferenceByString($name);

                continue;
            }

            // Short name — resolve via use map
            if (isset($this->useMap[$name])) {
                $this->addReferenceByString($this->useMap[$name]);
            }
        }

        // Also catch bare FQCNs anywhere in docblock (e.g., inside generics like Collection<App\Models\User>)
        preg_match_all('/\\\\?((?:[A-Z]\w*\\\\)+[A-Z]\w*)/', $text, $fqcnMatches);
        foreach ($fqcnMatches[1] as $fqcn) {
            $this->addReferenceByString(ltrim($fqcn, '\\'));
        }
    }

    private function addReference(Name $name): void
    {
        $this->addReferenceByString($name->toString());
    }

    private function addReferenceByString(string $resolved): void
    {
        if (in_array(strtolower($resolved), self::BUILTIN_TYPES, true)) {
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
}
