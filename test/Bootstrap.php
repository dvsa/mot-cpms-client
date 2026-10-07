<?php

//Bootstrap file required in the root test directory by BJSS
use CpmsClientTest\Bootstrap;

require_once __DIR__ . '/CpmsClientTest/Bootstrap.php';

$path = realpath(__DIR__);

if ($path === false) {
    throw new RuntimeException('Unable to resolve the test directory.');
}

chdir(dirname($path));

Bootstrap::getInstance()->init($path, array('CpmsClientTest'));
