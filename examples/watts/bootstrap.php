<?php

use Davos\Graphy\Manager\Factory\ManagerFactory;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/Power.php';

$config = require __DIR__ . '/config.php';

ManagerFactory::configure($config);

return $config;
