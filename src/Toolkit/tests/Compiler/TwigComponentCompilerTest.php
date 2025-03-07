<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Compiler\Exception\TwigComponentAlreadyExist;
use Symfony\UX\Toolkit\Compiler\TwigComponentCompiler;
use Symfony\UX\Toolkit\Registry\DependenciesResolver;
use Symfony\UX\Toolkit\Registry\Registry;
use Symfony\UX\Toolkit\Registry\RegistryItem;
use Symfony\UX\Toolkit\Registry\RegistryItemType;

/**
 * @author Jean-François Lépine
 */
class TwigComponentCompilerTest extends TestCase
{
    public function testItShouldCompileComponentToFile(): void
    {
        $compiler = new TwigComponentCompiler('Acme', new DependenciesResolver());
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('component_');

        $registry = new Registry();
        $item = new RegistryItem(
            'Badge',
            RegistryItemType::Component,
            'default',
            null,
            '<button>foo</button>'
        );
        $registry->add($item);

        $compiler->compile($registry, $item, $destination);

        $this->assertFileExists($destination);
        $this->assertFileExists($destination.'/Acme/Badge.html.twig');

        $content = file_get_contents($destination.'/Acme/Badge.html.twig');
        $this->assertStringContainsString('<button>foo</button>', $content);
    }

    public function testShouldThrowExceptionIfFileAlreadyExist(): void
    {
        $compiler = new TwigComponentCompiler('Acme', new DependenciesResolver());
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('component_');

        $registry = new Registry();
        $item = new RegistryItem(
            'Badge',
            RegistryItemType::Component,
            'default',
            null,
            '<button>foo</button>'
        );
        $registry->add($item);

        $compiler->compile($registry, $item, $destination);

        $this->expectException(TwigComponentAlreadyExist::class);
        $compiler->compile($registry, $item, $destination);
    }

    public function testDependenciesAreAlsoCompiled(): void
    {
        $compiler = new TwigComponentCompiler('Acme', new DependenciesResolver());
        $destination = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('component_');

        $registry = new Registry();
        $registry->add(
            new RegistryItem(
                'Badge',
                RegistryItemType::Component,
                'default',
                null,
                '<button>foo</button>'
            )
        );
        $registry->add(
            new RegistryItem(
                'Table',
                RegistryItemType::Component,
                'default',
                null,
                '<table>foo</table>'
            )
        );
        $registry->add(
            new RegistryItem(
                'TableRow',
                RegistryItemType::Component,
                'default',
                'Table',
                '<tr>foo</tr>'
            )
        );

        $compiler->compile($registry, $registry->get('TableRow'), $destination);

        $this->assertFileExists($destination);
        $this->assertFileExists($destination.'/Acme/Table.html.twig');
        $this->assertFileExists($destination.'/Acme/TableRow.html.twig');
        $this->assertFileDoesNotExist($destination.'/Acme/Badge.html.twig');
    }
}
