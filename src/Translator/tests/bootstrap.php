<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Translator\Tests\Kernel\FrameworkAppKernel;

require __DIR__.'/../vendor/autoload.php';

(new Filesystem())->remove(__DIR__.'/../var');

$kernel = new FrameworkAppKernel('test', true);
$application = new Application($kernel);

// Trigger Symfony Translator and UX Translator cache warmers
$application->run(new StringInput('cache:clear'));
