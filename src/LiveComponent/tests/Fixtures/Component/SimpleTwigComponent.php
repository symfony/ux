<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
#[AsTwigComponent('simple_twig')]
final class SimpleTwigComponent
{
    public string $foo = 'foo';

    public string $bar = 'bar';

    public function getFooBar(): string
    {
        return $this->foo.$this->bar;
    }
}
