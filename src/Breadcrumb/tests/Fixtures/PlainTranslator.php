<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Fixtures;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * Implements TranslatorInterface and nothing else, like a decorator that does not declare LocaleAwareInterface.
 */
final class PlainTranslator implements TranslatorInterface
{
    use TranslatorTrait;
}
