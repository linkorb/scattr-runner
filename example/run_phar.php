<?php

require_once __DIR__.'/../vendor/autoload.php';

define('APP_ROOT', __DIR__);

putenv('SCATTR_USERNAME=johndoe');
putenv('SCATTR_PASSWORD=secret');
putenv('SCATTR_ACCOUNT=test');
putenv('SCATTR_POOLNAME=infrastructureName');
putenv('SCATTR_URL=houston.local');

passthru('php runner.phar example\jobs.json');
