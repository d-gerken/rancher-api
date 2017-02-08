#!/usr/bin/env php
<?php

/* Rquire autoloading of composer */
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/* Init console application */
$application = new Application();

/* Add global options */
$application->getDefinition()->addOptions([
    new InputOption('access-key', '-ac', InputOption::VALUE_REQUIRED, 'Access-Key for Rancher-Api'),
    new InputOption('secret-key', '-sc', InputOption::VALUE_REQUIRED, 'Secret-Key for Rancher-Api')
]);

/* Add global arguments */
$application->getDefinition()->addArguments([
    new InputArgument('endpoint', InputArgument::REQUIRED, 'Endpoint of Rancher-Api')
]);

/* Add Commands */
$application->add(new RancherApi\Command\ExecuteCommand());
$application->run();
