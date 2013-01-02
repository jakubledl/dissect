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
    // credit goes to everzet & kostiklv, since
    // I copied the BehatApplication class when
    // dealing with some CLI problems.
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
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display version information.'),
        ));
    }
}
