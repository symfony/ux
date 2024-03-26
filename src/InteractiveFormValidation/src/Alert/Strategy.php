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

namespace Symfony\UX\InteractiveFormValidation\Alert;

/**
 * @author Mateusz Anders <anders_mateusz@outlook.com>
 */
enum Strategy: string
{
    case BROWSER_NATIVE = 'browser_native';
    case EMIT_EVENT = 'emit_event';
}