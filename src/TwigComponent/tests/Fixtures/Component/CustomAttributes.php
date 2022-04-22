<?php

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsTwigComponent('custom_attributes', attributesVar: '_attributes')]
final class CustomAttributes
{
}
