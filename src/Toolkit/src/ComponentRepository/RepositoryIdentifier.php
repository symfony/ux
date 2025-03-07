<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\ComponentRepository;

/**
 * @author Jean-François Lépine
 *
 * @internal
 */
final class RepositoryIdentifier
{
    public function identify(string $name): RepositoryIdentity
    {
        if (preg_match('!^\w+$!', $name)) {
            // Official repository (with only the theme name)
            return new RepositoryIdentity(
                RepositorySources::EMBEDDED,
                'symfony',
                $name,
                null
            );
        }

        $name = preg_replace('!^(https://|http://)!', '', $name);
        if (preg_match('!^github.com/([\-\w]+)/([\-\w]+)(@.+)?$!', $name, $matches)) {
            // github.com/vendor/package@version
            // github.com/vendor/package
            // https://github.com/vendor/package
            return new RepositoryIdentity(
                RepositorySources::GITHUB,
                $matches[1],
                $matches[2],
                trim($matches[3] ?? 'main', '@')
            );
        }

        throw new \InvalidArgumentException('Source is not supported for this component.');
    }
}
