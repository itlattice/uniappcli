<?php

use process\Process;

require_once __DIR__ . '/vendor/autoload.php';

echo "Starting Uniapp Cli Pack...\n";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$process=new Process($_ENV);
$process->run();
