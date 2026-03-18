<?php declare(strict_types=1);

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../../../../vendor/autoload.php';

// The plugin is not registered as a Composer path-repository in the root project,
// so we add the PSR-4 mappings manually for unit testing.
$loader->addPsr4('AlengoOrderSearch\\', __DIR__ . '/../src/');
$loader->addPsr4('AlengoOrderSearch\\Tests\\', __DIR__ . '/');
