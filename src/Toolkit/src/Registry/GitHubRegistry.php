<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Registry;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitFactory;

/**
 * @internal
 *
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class GitHubRegistry implements RegistryInterface
{
    public function __construct(
        private readonly KitFactory $kitFactory,
        private readonly Filesystem $filesystem,
        private ?HttpClientInterface $httpClient = null,
    ) {
        if (null === $httpClient) {
            if (!class_exists(HttpClient::class)) {
                throw new \LogicException('You must install "symfony/http-client" to use the UX Toolkit with remote components. Try running "composer require symfony/http-client".');
            }

            $this->httpClient = HttpClient::create();
        }

        if (!class_exists(\ZipArchive::class)) {
            throw new \LogicException('You must have the Zip extension installed to use UX Toolkit with remote registry.');
        }
    }

    /**
     * @see https://regex101.com/r/0BoRNX/1
     */
    public const RE_GITHUB_KIT = '/^(?:https:\/\/)?(github\.com)\/(?<authorName>[\w-]+)\/(?<repositoryName>[\w-]+)(?::(?<version>[\w._-]+))?$/';

    public static function supports(string $kitName): bool
    {
        return 1 === preg_match(self::RE_GITHUB_KIT, $kitName);
    }

    public function getKit(string $kitName): Kit
    {
        $repositoryDir = $this->downloadRepository(GitHubRegistryIdentity::fromUrl($kitName));

        return $this->kitFactory->createKitFromAbsolutePath($repositoryDir);
    }

    /**
     * @throws \RuntimeException
     */
    private function downloadRepository(GitHubRegistryIdentity $identity): string
    {
        $zipUrl = \sprintf(
            'https://github.com/%s/%s/archive/%s.zip',
            $identity->authorName,
            $identity->repositoryName,
            $identity->version,
        );

        $tmpDir = $this->createTmpDir();
        $archiveExtractedName = \sprintf('%s-%s', $identity->repositoryName, $identity->version);
        $archiveName = \sprintf('%s.zip', $archiveExtractedName);
        $archivePath = Path::join($tmpDir, $archiveName);
        $archiveExtractedDir = Path::join($tmpDir, $archiveExtractedName);

        // Download and stream the archive
        $response = $this->httpClient->request('GET', $zipUrl);
        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException(\sprintf('Unable to download the archive from "%s", ensure the repository exists and the version is valid.', $zipUrl));
        }

        $archiveResource = fopen($archivePath, 'w');
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($archiveResource, $chunk->getContent());
        }
        fclose($archiveResource);

        // Extract the archive
        $zip = new \ZipArchive();
        $zip->open($archivePath);
        $zip->extractTo($tmpDir);
        $zip->close();

        if (!$this->filesystem->exists($archiveExtractedDir)) {
            throw new \RuntimeException(\sprintf('Unable to extract the archive from "%s", ensure the repository exists and the version is valid.', $zipUrl));
        }

        return $archiveExtractedDir;
    }

    private function createTmpDir(): string
    {
        $dir = $this->filesystem->tempnam(sys_get_temp_dir(), 'ux_toolkit_github_');
        $this->filesystem->remove($dir);
        $this->filesystem->mkdir($dir);

        return $dir;
    }
}
