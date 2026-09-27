<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Provider;

use Symfony\UX\Image\Exception\InvalidArgumentException;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
abstract class AbstractProviderFactory
{
    public function supports(Dsn $dsn): bool
    {
        return \in_array($dsn->getScheme(), $this->getSupportedSchemes(), true);
    }

    /**
     * @return list<string>
     */
    abstract protected function getSupportedSchemes(): array;

    /**
     * @return list<string> the DSN query options this factory reads
     */
    protected function getSupportedOptions(): array
    {
        return [];
    }

    /**
     * @throws InvalidArgumentException when the DSN carries an unsupported option, or an option that is not a string
     */
    protected function validateOptions(Dsn $dsn): void
    {
        $supported = $this->getSupportedOptions();
        $options = $dsn->getOptions();

        if ($unsupported = array_diff(array_keys($options), $supported)) {
            throw new InvalidArgumentException(\sprintf('Invalid option(s) "%s" passed to the "%s" image provider (supported: %s).', implode('", "', $unsupported), $dsn->getScheme(), [] === $supported ? 'none' : '"'.implode('", "', $supported).'"'));
        }

        foreach ($options as $name => $value) {
            if (!\is_string($value)) {
                throw new InvalidArgumentException(\sprintf('The "%s" option of the "%s" image provider must be a string, "%s" given.', $name, $dsn->getScheme(), get_debug_type($value)));
            }
        }
    }
}
