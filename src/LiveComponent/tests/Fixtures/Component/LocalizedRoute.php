<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @author Simon André
 */
#[AsLiveComponent('localized_route', route: 'localized_route')]
final class LocalizedRoute
{
    use DefaultActionTrait;
}
