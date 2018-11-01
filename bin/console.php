#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;

if (!isset($_SERVER['APP_ENV'])) {
    $dotenv = new \Symfony\Component\Dotenv\Dotenv();
    $dotenv->load(__DIR__.'/../.env');
}

$application = new Application();
$application->add(new \App\FetchDiscogsCollectionCommand());
$application->run();
