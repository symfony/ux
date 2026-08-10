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
use Symfony\UX\LiveComponent\LiveResponse;

#[AsLiveComponent('download_from_default_action', template: 'components/download_file.html.twig')]
class DownloadFromDefaultActionComponent
{
    public int $downloadCount = 0;

    /**
     * The default action runs on every re-render, polling included: returning a download
     * here must be rejected rather than fire a file every few hundred milliseconds.
     */
    public function __invoke(): LiveResponse
    {
        return LiveResponse::downloadFile('from the default action', 'default.txt', 'text/plain');
    }
}
