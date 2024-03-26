<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\UX\InteractiveFormValidation;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Mateusz Anders <anders_mateusz@outlook.com>
 */
final class InteractiveFormValidationBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}