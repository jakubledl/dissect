<?php

namespace Dissect\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * The CLI application.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class Application extends BaseApplication
{
    public function __construct($version)
    {
        parent::__construct('Dissect', $version);
    }

    protected function getCommandName(InputInterface $input)
    {
        return 'dissect';
    }

    protected function getDefaultCommands()
    {
        $default = parent::getDefaultCommands();
        $default[] = new Command\DissectCommand();

        return $default;
    }

    public function getDefinition()
    {
        return new InputDefinition(array(
            new InputOption('--help',    '-h', InputOption::VALUE_NONE, 'Display this help message.'),
            new InputOption('--verbose', '-v', InputOption::VALUE_NONE, 'Increase verbosity of exceptions.'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this behat version.'),
            new InputOption('--config',  '-c', InputOption::VALUE_REQUIRED, 'Specify config file to use.'),
            new InputOption('--profile', '-p', InputOption::VALUE_REQUIRED, 'Specify config profile to use.')
        ));
    }

    // protected function getTerminalWidth()
    // {
    //     return PHP_INT_MAX;
    // }
}
