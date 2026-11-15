<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\ErrorHandler\ErrorHandler;

require __DIR__.'/../vendor/autoload.php';

// @see https://github.com/symfony/symfony/issues/53812
ErrorHandler::register(null, false);

// Compile the test kernels from scratch, so that the bundle changes apply
new Symfony\Component\Filesystem\Filesystem()->remove(sys_get_temp_dir().'/ux_data_table');
