<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Exception;

use Symfony\UX\Map\MapOptionsInterface;

final class UnableToNormalizeOptionsException extends LogicException
{
    public function __construct(string $message)
    {
        parent::__construct(\sprintf('Unable to normalize the map options: %s', $message));
    }

    /**
     * @param class-string<MapOptionsInterface> $optionsClass
     */
    public static function unsupportedProviderClass(string $optionsClass): self
    {
        return new self(\sprintf('the class "%s" is not supported.', $optionsClass));
    }
}
