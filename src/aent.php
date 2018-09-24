#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use \TheAentMachine\Aent\BootstrapAent;
use \TheAentMachine\AentBootstrap\Event\AddEvent;

$application = new BootstrapAent('Bootstrap', new AddEvent());
$application->run();
