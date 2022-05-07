<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsLiveComponent('custom_attributes', attributesVar: '_custom_attributes')]
final class CustomAttributes
{

}
