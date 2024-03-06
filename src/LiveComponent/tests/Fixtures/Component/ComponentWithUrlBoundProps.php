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
use Symfony\UX\LiveComponent\Metadata\UrlMapping;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Address;

#[AsLiveComponent('component_with_url_bound_props')]
class ComponentWithUrlBoundProps
{
    use DefaultActionTrait;

    #[LiveProp(url: true)]
    public ?string $stringProp = null;

    #[LiveProp(url: true)]
    public ?int $intProp = null;

    #[LiveProp(url: true)]
    public array $arrayProp = [];

    #[LiveProp]
    public ?string $unboundProp = null;

    #[LiveProp(url: true)]
    public ?Address $objectProp = null;

    #[LiveProp(fieldName: 'field1', url: true)]
    public ?string $propWithField1 = null;

    #[LiveProp(fieldName: 'getField2()', url: true)]
    public ?string $propWithField2 = null;

    #[LiveProp(modifier: 'modifyMaybeBoundProp')]
    public ?string $maybeBoundProp = null;

    #[LiveProp]
    public ?bool $maybeBoundPropInUrl = false;

    public function getField2(): string
    {
        return 'field2';
    }

    public function modifyMaybeBoundProp(LiveProp $prop): LiveProp
    {
        return $prop->withUrl($this->maybeBoundPropInUrl);
    }

    #[LiveProp(url: new UrlMapping(as: 'q'))]
    public ?string $boundPropWithAlias = null;

    #[LiveProp(url: true, modifier: 'modifyBoundPropWithCustomAlias')]
    public ?string $boundPropWithCustomAlias = null;

    #[LiveProp]
    public ?string $customAlias = null;

    public function modifyBoundPropWithCustomAlias(LiveProp $liveProp): LiveProp
    {
        if ($this->customAlias) {
            $liveProp = $liveProp->withUrl(new UrlMapping(as: $this->customAlias));
        }

        return $liveProp;
    }


}
