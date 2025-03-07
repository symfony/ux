<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Console\Test\InteractsWithConsole;

/**
 * @author Jean-François Lépine
 */
class BuildRegistryCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    public function testShouldBeAbleToBuildRegistry(): void
    {
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid();
        mkdir($destination);

        $this->bootKernel();
        $this->consoleCommand('ux:toolkit:build-registry --destination='.$destination)
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('default/components/Alert.html.twig')
            ->assertOutputContains('default/components/Table.html.twig')
            ->assertOutputContains('default/components/Table/TableHeader.html.twig')
        ;

        $this->assertFileExists($destination.\DIRECTORY_SEPARATOR.'registry.json');
        $this->assertFileExists($destination.\DIRECTORY_SEPARATOR.'components/Alert.json');
        $this->assertFileExists($destination.\DIRECTORY_SEPARATOR.'components/Table.json');
        $this->assertFileExists($destination.\DIRECTORY_SEPARATOR.'components/Table/TableRow.json');

        $row = json_decode(file_get_contents($destination.\DIRECTORY_SEPARATOR.'components/Table/TableRow.json'), true);
        $this->assertSame('TableRow', $row['name']);
        $this->assertNotEmpty($row['code']);
        $this->assertSame(md5($row['code']), $row['fingerprint']);
    }

    public function testShouldBeAbleToBuildRegistryWithMetadata(): void
    {
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid();
        mkdir($destination);

        $this->bootKernel();
        $this->consoleCommand("ux:toolkit:build-registry --licenses=MIT --licenses=CECIL-B --destination=$destination SymfonyUX 'https://www.symfony.com'")
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('default/components/Alert.html.twig')
            ->assertOutputContains('default/components/Table.html.twig')
            ->assertOutputContains('default/components/Table/TableHeader.html.twig')
        ;

        $this->assertFileExists($destination.\DIRECTORY_SEPARATOR.'registry.json');
        $this->assertFileExists($destination.\DIRECTORY_SEPARATOR.'components/Alert.json');
        $this->assertFileExists($destination.\DIRECTORY_SEPARATOR.'components/Table.json');
        $this->assertFileExists($destination.\DIRECTORY_SEPARATOR.'components/Table/TableRow.json');

        $json = json_decode(file_get_contents($destination.\DIRECTORY_SEPARATOR.'registry.json'), true);
        $this->assertSame(['MIT', 'CECIL-B'], $json['licenses']);
        $this->assertSame('https://www.symfony.com', $json['homepage']);
        $this->assertSame('SymfonyUX', $json['name']);
    }
}
