<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\ComponentRepository;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Jean-François Lépine
 *
 * @internal
 */
final readonly class GithubRepository implements ComponentRepository
{
    public function __construct(
        private Filesystem $filesystem,
        private ?HttpClientInterface $httpClient = null,
    ) {
        if (!class_exists(HttpClient::class)) {
            throw new \LogicException('You must install "symfony/http-client" to use the UX Toolkit with remote components. Try running "composer require symfony/http-client".');
        }

        if (!class_exists(\ZipArchive::class)) {
            throw new \LogicException('You must have the Zip extension installed to use UX Toolit with remote components.');
        }
    }

    public function fetch(RepositoryIdentity $repository): Finder
    {
        // download a zip file of the github repository, place it in a temporary directory in cache
        $zipUrl = \sprintf(
            'http://github.com/%s/%s/archive/%s.zip',
            $repository->getVendor(),
            $repository->getPackage(),
            $repository->getVersion(),
        );

        $destination = $this->getCacheDir($repository);
        $finder = new Finder();
        $finder->files()->in($destination);

        $searcher = clone $finder;
        $searcher->files()->name('registry.json');
        if ($searcher->hasResults()) {
            return $finder;
        }

        $zipFile = $destination.'/'.basename($zipUrl);
        $response = $this->httpClient->request('GET', $zipUrl, []);

        // Ensure the request was successful
        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException(\sprintf('Failed to download the file from "%s".', $zipUrl));
        }

        // Ensure response contains valid headers
        $headers = $response->getHeaders();
        if (!isset($headers['content-type']) || !\in_array('application/zip', $headers['content-type'])) {
            throw new \RuntimeException(\sprintf('The file from "%s" is not a valid zip file.', $zipUrl));
        }

        // Flush the response to the file
        $this->filesystem->dumpFile($zipFile, $response->getContent());

        // unzip the file
        $zip = new \ZipArchive();
        $zip->open($zipFile);
        $zip->extractTo($destination);
        $zip->close();

        return $finder;
    }

    public function getCacheDir(RepositoryIdentity $repository): string
    {
        return $this->createTmpDir('cache', $repository);
    }

    private function createTmpDir(string $type, RepositoryIdentity $repository): string
    {
        $hash = md5($repository->getVendor().$repository->getPackage().$repository->getVersion());
        $dir = sys_get_temp_dir().'/ux_toolkit/'.$type.'/'.$hash;

        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir);
        }

        return $dir;
    }
}
