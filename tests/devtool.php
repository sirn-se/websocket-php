<?php

use Symfony\Component\Console\Application;
use WebSocket\Test\DevTool\FrameEncodeCommand;

require __DIR__ . '/../vendor/autoload.php';

$application = new Application('WebSocket DevTool', '4.0');
$application->addCommands([
    new FrameEncodeCommand(),
]);
$application->run();
