<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Registry;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\Icon;
use Symfony\UX\Icons\IconRegistryInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ChainIconRegistry implements IconRegistryInterface
{
    /**
     * @param IconRegistryInterface[] $registries
     */
    public function __construct(private iterable $registries)
    {
    }

    public function get(string $name): Icon
    {
        foreach ($this->registries as $registry) {
            try {
                return $registry->get($name);
            } catch (IconNotFoundException) {
            }
        }

        $exceptionExtra = '';
        if (str_contains($name, ':') && !class_exists(HttpClient::class)) {
            $exceptionExtra = ' If you want the on-demand registry to fetch the icon from iconfiy, you need to install "symfony/http-client".';
        }

        throw new IconNotFoundException(\sprintf('Icon "%s" not found.%s', $name, $exceptionExtra));
    }
}
