<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Service;

use Symfony\UX\TwigComponent\AnonymousComponentRegistryInterface;

class AnonymousComponentRegistry implements AnonymousComponentRegistryInterface
{
    public function register(): array
    {
        return [
            'ux:Icon' => 'components/Icon.html.twig',
        ];
    }

    public function get(string $name): ?string
    {
        return $this->register()[$name] ?? null;
    }
}
