<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Fixtures;

use Symfony\UX\Image\Provider\AbstractProviderFactory;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Provider\ProviderFactoryInterface;
use Symfony\UX\Image\Provider\ProviderInterface;

final class FakeProviderFactory extends AbstractProviderFactory implements ProviderFactoryInterface
{
    public function create(Dsn $dsn): ProviderInterface
    {
        return new FakeProvider(autoFormat: (bool) $dsn->getOption('auto_format', true));
    }

    protected function getSupportedSchemes(): array
    {
        return ['fake'];
    }
}
