<?php

define('DISSECT_VERSION', 'DEV');

if (is_dir($vendor = __DIR__ . '/../vendor')) {
    require $vendor . '/autoload.php';
} elseif (is_dir($vendor = __DIR__ . '/../../../../vendor')) {
    require $vendor . '/autoload.php';
} else {
    die(
        'You must set up the project dependecies, run the following commands:' . PHP_EOL .
        'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );
}

if (!class_exists('Symfony\\Component\\Console\\Application')) {
    die('The command-line interface requires the symfony/console package.' . PHP_EOL);
}

$app = new Dissect\Console\Application(DISSECT_VERSION);
$app->run();
