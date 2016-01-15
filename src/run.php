<?php

require_once __DIR__.'/../vendor/autoload.php';

use Scattr\Runner\JobApplication;

define('APP_ROOT', __DIR__);

$application = new JobApplication();
$application->run();