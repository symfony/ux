<?php

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsTwigComponent(name: 'no_public_props', exposePublicProps: false)]
final class NoPublicProps
{
    public $prop1 = 'value';
}
