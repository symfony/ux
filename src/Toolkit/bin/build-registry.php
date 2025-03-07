#!/usr/bin/env php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../vendor/autoload.php';

$app = new Symfony\Component\Console\Application('Symfony UX Toolkit Builder', '0.1');
$compiler = new Symfony\UX\Toolkit\Compiler\RegistryCompiler(new Symfony\Component\Filesystem\Filesystem());
$app->add(new Symfony\UX\Toolkit\Command\BuildRegistryCommand($compiler));
$app->setDefaultCommand('ux:toolkit:build-registry', true);
$app->run();
