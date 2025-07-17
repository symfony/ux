<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @author Gábor Egyed <gabor.egyed@gmail.com>
 */
#[AsLiveComponent('with_absolute_url', urlReferenceType: UrlGeneratorInterface::ABSOLUTE_URL)]
final class WithAbsoluteUrl
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public int $count = 0;

    #[LiveAction]
    public function increase(): void
    {
        ++$this->count;
    }
}
