<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\ComponentRepository;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\UX\Toolkit\ComponentRepository\GithubRepository;
use Symfony\UX\Toolkit\ComponentRepository\RepositoryIdentity;
use Symfony\UX\Toolkit\ComponentRepository\RepositorySources;

/**
 * @author Jean-François Lépine
 */
class GithubRepositoryTest extends TestCase
{
    public function testGithubRepositoryUseClientAndTryToDownloadRemoteFile(): void
    {
        // Create a zip file with a pseudo manifest.json file
        $manifest = '{"name:""Haleck45/ux-toolkit","version":"1.0.0"}';
        $workdir = sys_get_temp_dir().'/ux-toolkit';
        $filesystem = new Filesystem();
        $filesystem->mkdir($workdir);
        $filesystem->dumpFile($workdir.'/manifest.json', $manifest);
        $filesystem->dumpFile($workdir.'/README.md', 'My readme content');

        $zip = new \ZipArchive();
        $zip->open($workdir.'/ux-toolkit-1.0.0.zip', \ZipArchive::CREATE);
        $zip->addFile($workdir.'/manifest.json', 'manifest.json');
        $zip->addFile($workdir.'/README.md', 'README.md');
        $zip->close();

        // Create a mock http client that will return the zip file
        $client = new MockHttpClient();
        $client->setResponseFactory(fn () => new MockResponse(
            file_get_contents($workdir.'/ux-toolkit-1.0.0.zip'),
            [
                'http_code' => 200,
                'response_headers' => [
                    'content-type' => 'application/zip',
                ],
            ]
        ));

        $filesystem = new Filesystem();
        $repository = new GithubRepository($filesystem, $client);

        $component = new RepositoryIdentity(RepositorySources::GITHUB, 'Halleck45', 'ux-toolkit', '1.0.0');
        $finder = $repository->fetch($component);

        // the manifest file should be extracted
        $manifestFile = $finder->files()->path('manifest.json')->count();
        $this->assertSame(1, $manifestFile);
    }

    public function testGithubRepositorybUTwITHiNVALIDhEADERS(): void
    {
        // Create a zip file with a pseudo manifest.json file
        $manifest = '{"name:""Haleck45/ux-toolkit","version":"1.0.0"}';
        $workdir = sys_get_temp_dir().'/ux-toolkit';
        $filesystem = new Filesystem();
        $filesystem->mkdir($workdir);
        $filesystem->dumpFile($workdir.'/manifest.json', $manifest);
        $filesystem->dumpFile($workdir.'/README.md', 'My readme content');

        $zip = new \ZipArchive();
        $zip->open($workdir.'/ux-toolkit-1.0.0.zip', \ZipArchive::CREATE);
        $zip->addFile($workdir.'/manifest.json', 'manifest.json');
        $zip->addFile($workdir.'/README.md', 'README.md');
        $zip->close();

        // Create a mock http client that will return the zip file
        $client = new MockHttpClient();
        $client->setResponseFactory(fn () => new MockResponse(
            file_get_contents($workdir.'/ux-toolkit-1.0.0.zip'),
            [
                'http_code' => 200,
                'response_headers' => [
                    'content-type' => 'application/json',
                ],
            ]
        ));

        $filesystem = new Filesystem();
        $repository = new GithubRepository($filesystem, $client);

        $component = new RepositoryIdentity(RepositorySources::GITHUB, 'Halleck45', 'ux-toolkit', '1.0.0');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The file from "http://github.com/Halleck45/ux-toolkit/archive/1.0.0.zip" is not a valid zip file.');

        $repository->fetch($component);
    }
}
