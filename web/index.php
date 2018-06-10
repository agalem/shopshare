<?php

umask(0002); // This will let the permissions be 0777
// ...

ini_set('display_errors', 0);

require_once __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../config/prod.php';
require __DIR__.'/../src/controllers.php';

$app->register(new Silex\Provider\HttpFragmentServiceProvider());
$app->run();
