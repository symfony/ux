<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsLiveComponent('with_attributes')]
final class ComponentWithAttributes
{
    use DefaultActionTrait;
}
