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
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponse;

#[AsLiveComponent('live_data', template: 'components/live_data.html.twig')]
class DataComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $searchCount = 0;

    #[LiveAction]
    public function search(#[LiveArg] string $query = ''): LiveResponse
    {
        ++$this->searchCount;

        return LiveResponse::data(['query' => $query, 'results' => ['résumé', 'foo']]);
    }

    #[LiveAction]
    public function searchAsXml(): LiveResponse
    {
        ++$this->searchCount;

        return LiveResponse::data('<results><result>foo</result></results>', 'application/xml');
    }

    #[LiveAction]
    public function binary(): LiveResponse
    {
        return LiveResponse::data("\x00\x01\x02\xFF\xFE", 'application/octet-stream');
    }

    #[LiveAction]
    public function download(): LiveResponse
    {
        return LiveResponse::downloadFile('a,b,c', 'report.csv', 'text/csv');
    }
}
