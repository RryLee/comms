<?php

	$loader = (require __DIR__ . '/../vendor/autoload.php');
	$namespaces = $loader->getPrefixesPsr4();

	foreach ($namespaces as $namespace => $dir) {
		$loader->addPsr4($namespace . 'Tests\\', __DIR__);
	}