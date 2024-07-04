<?php

use CpmsClientTest\Bootstrap;

$path = realpath(__DIR__ . '/../');
if ($path !== false) {
    chdir(dirname($path));
    Bootstrap::getInstance()->init($path, ['CpmsClientTest']);
} else {
    throw new \RuntimeException('Failed to resolve real path when initializing test Bootstrap.');
}
