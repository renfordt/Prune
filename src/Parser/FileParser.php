<?php

declare(strict_types=1);

namespace Renfordt\Prune\Parser;

use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class FileParser
{
    private readonly Parser $parser;

    public function __construct()
    {
        $this->parser = new ParserFactory()->createForNewestSupportedVersion();
    }

    /**
     * @return Stmt[]
     */
    public function parse(string $filePath): array
    {
        $code = file_get_contents($filePath);
        if ($code === false) {
            return [];
        }

        $stmts = $this->parser->parse($code);

        return $stmts ?? [];
    }
}
