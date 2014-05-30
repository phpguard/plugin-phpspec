<?php

$loader = require __DIR__ . '/../vendor/autoload.php';

$basedir = realpath(__DIR__.'/..');
$loader->addPsr4('PhpGuard\\Plugins\\PhpSpec\\Functional\\', $basedir.'/functional');
$loader->register();