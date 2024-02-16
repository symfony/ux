<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Address;

#[AsLiveComponent('component_with_url_bound_props')]
class ComponentWithUrlBoundProps
{
    use DefaultActionTrait;

    #[LiveProp(url: true)]
    public ?string $prop1 = null;

    #[LiveProp(url: true)]
    public ?int $prop2 = null;

    #[LiveProp(url: true)]
    public array $prop3 = [];

    #[LiveProp]
    public ?string $prop4 = null;

    #[LiveProp(url: true)]
    public ?Address $prop5 = null;

    #[LiveProp(fieldName: 'field6', url: true)]
    public ?string $prop6 = null;

    #[LiveProp(fieldName: 'getProp7Name()', url: true)]
    public ?string $prop7 = null;

    #[LiveProp(modifier: 'modifyProp8')]
    public ?string $prop8 = null;

    #[LiveProp]
    public ?bool $prop8InUrl = false;

    public function getProp7Name(): string
    {
        return 'field7';
    }

    public function modifyProp8(LiveProp $prop): LiveProp
    {
        return $prop->withUrl($this->prop8InUrl);
    }
}
