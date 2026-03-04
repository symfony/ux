<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

#[AsLiveComponent]
final class LiveComponentWithAliasedLiveProps
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: new UrlMapping(as: 'q'))]
    public string $query = '';

    #[LiveProp(writable: true, url: true, modifier: 'modifyCategoryProp')]
    public string $category = '';

    public function modifyCategoryProp(LiveProp $liveProp): LiveProp
    {
        return $liveProp->withUrl(new UrlMapping(as: 'cat'));
    }
}
