<?php

require_once __DIR__.'/../vendor/autoload.php';

putenv('SCATTR_USERNAME=johndoe');
putenv('SCATTR_PASSWORD=secret');
putenv('SCATTR_ACCOUNT=test');
putenv('SCATTR_POOLNAME=infrastructureName');
putenv('SCATTR_URL=houston.local');

passthru('php ../runner.phar jobs.json');
