<?php

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'union_type_props', exposePublicProps: true)]
final class UnionTypeProps
{
    public string|bool $prop1;
}
