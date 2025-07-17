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

final class UnableToDenormalizeOptionsException extends LogicException
{
    public function __construct(string $message)
    {
        parent::__construct(\sprintf('Unable to denormalize the map options: %s', $message));
    }

    public static function missingProviderKey(string $key): self
    {
        return new self(\sprintf('the provider key "%s" is missing in the normalized options.', $key));
    }

    public static function unsupportedProvider(string $provider, array $supportedProviders): self
    {
        return new self(\sprintf('the provider "%s" is not supported. Supported providers are "%s".', $provider, implode('", "', $supportedProviders)));
    }
}
