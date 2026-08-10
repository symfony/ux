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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponse;

#[AsLiveComponent('download_file', template: 'components/download_file.html.twig')]
class DownloadFileComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    private const FILE_DIRECTORY = __DIR__.'/../files/';

    /**
     * Proves the component still re-renders: a download must not cost the action's state.
     */
    #[LiveProp(writable: true)]
    public int $downloadCount = 0;

    /**
     * Named download() on purpose: it is the most natural name for an action, and it must
     * not clash with anything the component inherits.
     */
    #[LiveAction]
    public function download(): LiveResponse
    {
        ++$this->downloadCount;

        return LiveResponse::downloadFile(file_get_contents(self::FILE_DIRECTORY.'foo.json'), 'foo.json', 'application/json');
    }

    #[LiveAction]
    public function downloadWithoutContentType(): LiveResponse
    {
        return LiveResponse::downloadFile('plain content', 'notes.txt');
    }

    #[LiveAction]
    public function downloadNonAsciiFilename(): LiveResponse
    {
        return LiveResponse::downloadFile('résumé content', 'résumé.txt', 'text/plain');
    }

    #[LiveAction]
    public function downloadBinary(): LiveResponse
    {
        // bytes that are not valid UTF-8: proves the split happens before decoding
        return LiveResponse::downloadFile("\x00\x01\x02\xFF\xFE", 'blob.bin');
    }

    #[LiveAction]
    public function downloadEmpty(): LiveResponse
    {
        return LiveResponse::downloadFile('', 'empty.txt', 'text/plain');
    }

    #[LiveAction]
    public function downloadFromSplFileInfo(): LiveResponse
    {
        // no filename given: it comes from the basename, and the size from disk
        return LiveResponse::downloadFile(new \SplFileInfo(self::FILE_DIRECTORY.'foo.json'));
    }

    #[LiveAction]
    public function streamFromResource(): LiveResponse
    {
        ++$this->downloadCount;

        $path = self::FILE_DIRECTORY.'foo.json';

        return LiveResponse::downloadFile(fopen($path, 'r'), 'foo.json', 'application/json', filesize($path));
    }

    #[LiveAction]
    public function streamFromClosure(): LiveResponse
    {
        return LiveResponse::downloadFile(static function (): void {
            echo 'chunk-1';
            echo 'chunk-2';
        }, 'chunks.txt', 'text/plain');
    }

    #[LiveAction]
    public function streamFromGenerator(): LiveResponse
    {
        return LiveResponse::downloadFile(static function (): iterable {
            yield 'part-1';
            yield 'part-2';
        }, 'generated.txt', 'text/plain');
    }

    #[LiveAction]
    public function streamBinaryWithoutSize(): LiveResponse
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, "\x00\x01\x02\xFF\xFE");
        rewind($resource);

        return LiveResponse::downloadFile($resource, 'blob.bin');
    }

    #[LiveAction]
    public function downloadUrl(): LiveResponse
    {
        ++$this->downloadCount;

        return LiveResponse::downloadUrl('/downloads/report.csv');
    }

    #[LiveListener('refreshRequested')]
    public function onRefreshRequested(): LiveResponse
    {
        ++$this->downloadCount;

        return LiveResponse::downloadFile('from a listener', 'listener.txt', 'text/plain');
    }

    #[LiveAction]
    public function downloadAndDispatchBrowserEvent(): LiveResponse
    {
        $this->dispatchBrowserEvent('export:done', ['format' => 'csv']);

        return LiveResponse::downloadFile('with an event', 'evented.txt', 'text/plain');
    }

    #[LiveAction]
    public function downloadThenRedirect(): RedirectResponse
    {
        // returning a redirect instead of a LiveResponse: the two cannot be combined
        return new RedirectResponse('/');
    }

    #[LiveAction]
    public function noDownload(): void
    {
        ++$this->downloadCount;
    }

    #[LiveAction]
    public function exception(): void
    {
        throw new \RuntimeException('This action should not have been called.');
    }
}
