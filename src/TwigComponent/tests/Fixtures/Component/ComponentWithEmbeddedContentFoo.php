<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @author Bart Vanderstukken <bart.vanderstukken@gmail.com>
 */
#[AsTwigComponent('embedded_content_foo')]
final class ComponentWithEmbeddedContentFoo
{
    public function getSomething()
    {
        return 'you called Foo';
    }
}
