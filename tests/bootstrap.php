<?php

if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    $loader = (require __DIR__ . '/../vendor/autoload.php');
    $namespaces = $loader->getPrefixesPsr4();

    foreach ($namespaces as $namespace => $dir) {
        $loader->addPsr4($namespace . 'Tests\\', __DIR__);
    }
} else {
    require __DIR__ . '/../../autoload.php';
}
