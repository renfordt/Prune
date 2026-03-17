<?php

declare(strict_types=1);

namespace Renfordt\Prune;

use Renfordt\Prune\Command\AnalyzeCommand;
use Symfony\Component\Console\Application;

class PruneApplication extends Application
{
    public function __construct()
    {
        parent::__construct('Prune', '0.1.0');

        $command = new AnalyzeCommand();
        $this->addCommand($command);
        $this->setDefaultCommand((string) $command->getName(), true);
    }
}
