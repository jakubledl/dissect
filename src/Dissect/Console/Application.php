<?php

namespace Dissect\Console;

use Symfony\Component\Console\Application as BaseApplication;

/**
 * The console application.
 *
 * @author Jakub Lédl <jakubledl@gmail.com>
 */
class Application extends BaseApplication
{
    public function __construct($version)
    {
        parent::__construct('Dissect', $version);
    }
}
