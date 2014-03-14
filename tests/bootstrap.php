<?php

require __DIR__ . '/Autoloader.php';

session_start();

$pewLoader = new Autoloader("pew", __DIR__.'/../src');
$pewLoader->register();
