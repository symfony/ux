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

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponse;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
#[AsLiveComponent('download_file', template: 'components/download_file.html.twig')]
class DownloadFileComponent
{
    use DefaultActionTrait;

    private const FILE_DIRECTORY = __DIR__.'/../files/';

    #[LiveAction]
    public function download(): BinaryFileResponse
    {
        $file = new \SplFileInfo(self::FILE_DIRECTORY.'/foo.json');

        return LiveResponse::file($file);
    }

    #[LiveAction]
    public function generate(): BinaryFileResponse
    {
        $file = new \SplTempFileObject();
        $file->fwrite(file_get_contents(self::FILE_DIRECTORY.'/foo.json'));

        return LiveResponse::file($file, 'foo.json', size: 1000);
    }

    #[LiveAction]
    public function heavyFile(#[LiveArg] int $size): BinaryFileResponse
    {
        $file = new \SplFileInfo(self::FILE_DIRECTORY.'heavy.txt');

        $response = LiveResponse::file($file);
        $response->headers->set('Content-Length', 10000000); // 10MB
    }
}
