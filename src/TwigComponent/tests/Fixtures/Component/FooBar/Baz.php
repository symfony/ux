<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component\FooBar;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
#[AsTwigComponent('FooBar:Baz')]
final class Baz
{
}
