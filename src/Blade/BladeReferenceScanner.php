<?php

declare(strict_types=1);

namespace Renfordt\Prune\Blade;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class BladeReferenceScanner extends NodeVisitorAbstract
{
    /** @var array<string, true> */
    private array $references = [];

    public function enterNode(Node $node): null
    {
        // view('name') function call
        if ($node instanceof FuncCall && $node->name instanceof Name && $node->name->toString() === 'view') {
            $this->extractFirstStringArg($node->getArgs());
        }

        // View::make('name'), View::first([...])
        if ($node instanceof StaticCall && $node->class instanceof Name) {
            $className = $node->class->toString();
            if ($className === 'View' || str_ends_with($className, '\View')) {
                $methodName = $node->name instanceof Node\Identifier ? $node->name->toString() : null;
                if ($methodName === 'make') {
                    $this->extractFirstStringArg($node->getArgs());
                } elseif ($methodName === 'first') {
                    $this->extractArrayStringArgs($node->getArgs());
                }
            }
        }

        // Route::view('/path', 'name')
        if ($node instanceof StaticCall && $node->class instanceof Name) {
            $className = $node->class->toString();
            if ($className === 'Route' || str_ends_with($className, '\Route')) {
                $methodName = $node->name instanceof Node\Identifier ? $node->name->toString() : null;
                if ($methodName === 'view') {
                    $args = $node->getArgs();
                    if (count($args) >= 2 && $args[1]->value instanceof String_) {
                        $this->addReference($args[1]->value->value);
                    }
                }
            }
        }

        // $this->view('name'), $this->markdown('name')
        if ($node instanceof MethodCall && $node->var instanceof Node\Expr\Variable && $node->var->name === 'this') {
            $methodName = $node->name instanceof Node\Identifier ? $node->name->toString() : null;
            if ($methodName === 'view' || $methodName === 'markdown') {
                $this->extractFirstStringArg($node->getArgs());
            }
        }

        return null;
    }

    /**
     * @param  array<Arg>  $args
     */
    private function extractFirstStringArg(array $args): void
    {
        if ($args === []) {
            return;
        }

        if ($args[0]->value instanceof String_) {
            $this->addReference($args[0]->value->value);
        }
    }

    /**
     * @param  array<Arg>  $args
     */
    private function extractArrayStringArgs(array $args): void
    {
        if ($args === []) {
            return;
        }

        $firstArg = $args[0]->value;
        if ($firstArg instanceof Node\Expr\Array_) {
            foreach ($firstArg->items as $item) {
                if ($item->value instanceof String_) {
                    $this->addReference($item->value->value);
                }
            }
        }
    }

    private function addReference(string $viewName): void
    {
        $viewName = trim($viewName);
        if ($viewName !== '') {
            $this->references[$viewName] = true;
        }
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
