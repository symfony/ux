<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Registry;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\UX\Toolkit\Registry\GitHubRegistry;

final class GitHubRegistryTest extends KernelTestCase
{
    private Filesystem $filesystem;
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = self::getContainer()->get('filesystem');
        $this->tmpDir = $this->filesystem->tempnam(sys_get_temp_dir(), 'ux_toolkit_test_');
        $this->filesystem->remove($this->tmpDir);
        $this->filesystem->mkdir($this->tmpDir);
    }

    public function testCanGetKitFromGithub(): void
    {
        $isHttpClientCalled = false;
        $zipShadcnMain = $this->createZip('repo', 'shadcn', 'main');

        $httpClient = new MockHttpClient(function (string $method, string $url) use ($zipShadcnMain, &$isHttpClientCalled) {
            if ('GET' === $method && 'https://github.com/user/repo/archive/main.zip' === $url) {
                $isHttpClientCalled = true;

                return new MockResponse(
                    file_get_contents($zipShadcnMain),
                    [
                        'http_code' => 200,
                        'response_headers' => [
                            'content-type' => 'application/zip',
                        ],
                    ]
                );
            }
        });

        $githubRegistry = new GitHubRegistry(
            self::getContainer()->get('ux_toolkit.kit.factory'),
            $this->filesystem,
            $httpClient,
        );

        $kit = $githubRegistry->getKit('github.com/user/repo');

        $this->assertTrue($isHttpClientCalled);
        $this->assertSame('Shadcn UI', $kit->name);
        $this->assertNotEmpty($kit->getComponents());
        $this->assertFileExists($kit->path);
        $this->assertFileExists(Path::join($kit->path, 'templates/components/Button.html.twig'));
        $this->assertFileExists(Path::join($kit->path, 'docs/components/Button.md'));
    }

    public function testShouldThrowExceptionIfKitNotFound(): void
    {
        $githubRegistry = new GitHubRegistry(
            self::getContainer()->get('ux_toolkit.kit.factory'),
            $this->filesystem,
            new MockHttpClient(fn () => new MockResponse(
                'Not found',
                [
                    'http_code' => 404,
                ]
            )),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to download the archive from "https://github.com/user/repo/archive/main.zip", ensure the repository exists and the version is valid.');

        $githubRegistry->getKit('github.com/user/repo');
    }

    private function createZip(string $repo, string $kitName, string $version): string
    {
        $kitPath = Path::join(__DIR__, '..', '..', 'kits', $kitName);
        if (!$this->filesystem->exists($kitPath)) {
            throw new \RuntimeException(\sprintf('Kit "%s" not found in "%s".', $kitName, $kitPath));
        }

        $folderName = \sprintf('%s-%s', $repo, $version);
        $zip = new \ZipArchive();
        $zip->open($zipPath = \sprintf('%s/%s.zip', $this->tmpDir, $folderName), \ZipArchive::CREATE);
        foreach ((new Finder())->files()->in($kitPath) as $file) {
            $zip->addFile($file->getPathname(), Path::join($folderName, $file->getRelativePathname()));
        }
        $zip->close();

        return $zipPath;
    }
}
