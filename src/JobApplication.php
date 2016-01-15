<?php

namespace Scattr\Runner;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Scattr\Runner\Command\JobCommand;

class JobApplication extends Application
{
    protected function getCommandName(InputInterface $input)
    {
        return 'job';
    }

    protected function getDefaultCommands()
    {
        $defaultCommands = parent::getDefaultCommands();
        $defaultCommands[] = new JobCommand();

        return $defaultCommands;
    }

    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        $inputDefinition->setArguments();

        return $inputDefinition;
    }
}
