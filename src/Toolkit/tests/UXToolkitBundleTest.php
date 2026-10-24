<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\UX\Toolkit\Component\ComponentDocParser;
use Symfony\UX\Toolkit\Installer\InstallationDirectories;
use Symfony\UX\Toolkit\UXToolkitBundle;
use Twig\Environment;

class UXToolkitBundleTest extends KernelTestCase
{
    public function testBundleBuildsSuccessfully(): void
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();

        $this->assertInstanceOf(UXToolkitBundle::class, $container->get('kernel')->getBundles()['UXToolkitBundle']);
    }

    public function testComponentDocParserIsRegisteredAsAPublicService(): void
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();

        $this->assertInstanceOf(ComponentDocParser::class, $container->get('ux_toolkit.component.component_doc_parser'));
    }

    public function testToolkitTemplateNamespaceResolves(): void
    {
        self::bootKernel();

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        $this->assertTrue($twig->getLoader()->exists('@UXToolkit/markdown/alert.html.twig'));
    }

    public function testInstallationDirectoriesDefaultToTheConventionalLayout(): void
    {
        $config = $this->processConfiguration([]);

        $this->assertSame(['dir' => 'templates/components', 'subdirectory' => null], $config['components']);
        $this->assertSame(['dir' => 'assets/controllers', 'subdirectory' => null], $config['controllers']);
    }

    public function testInstallationDirectoriesAreGivenToTheInstallCommand(): void
    {
        $builder = new ContainerBuilder(new ParameterBag(['kernel.environment' => 'test', 'kernel.build_dir' => sys_get_temp_dir()]));
        new UXToolkitBundle()->getContainerExtension()->load([[
            'components' => ['subdirectory' => 'ui'],
            'controllers' => ['dir' => 'assets/js/controllers', 'subdirectory' => 'ui'],
        ]], $builder);

        $argument = $builder->getDefinition('.ux_toolkit.command.install')->getArgument(2);
        $directories = $builder->resolveServices($builder->getParameterBag()->resolveValue($argument));

        $this->assertInstanceOf(InstallationDirectories::class, $directories);
        $this->assertSame('templates/components/ui/Button.html.twig', $directories->resolve('%COMPONENTS_DIR%/Button.html.twig'));
        $this->assertSame('assets/js/controllers/ui/dialog_controller.js', $directories->resolve('%CONTROLLERS_DIR%/dialog_controller.js'));
        $this->assertSame('ui:', $directories->getComponentNamePrefix());
        $this->assertSame('ui--', $directories->getStimulusIdentifierPrefix());
    }

    /**
     * @return iterable<array{string, string, string}>
     */
    public static function provideInvalidInstallationDirectories(): iterable
    {
        yield 'empty directory' => ['controllers', 'dir', ''];
        yield 'directory escaping the project' => ['components', 'dir', '../../../../etc'];
        yield 'directory escaping the project halfway' => ['controllers', 'dir', 'assets/../../../etc'];
        yield 'directory escaping with backslashes' => ['controllers', 'dir', '..\\..\\etc'];
        yield 'project directory' => ['components', 'dir', '.'];
        yield 'absolute directory' => ['controllers', 'dir', '/etc'];
        yield 'Windows absolute directory' => ['components', 'dir', 'C:\\Windows'];
        yield 'network share directory' => ['controllers', 'dir', '\\\\server\\share'];
        yield 'stream wrapper directory' => ['components', 'dir', 'phar://templates'];
        yield 'null byte in directory' => ['controllers', 'dir', "assets\0"];
        yield 'empty subdirectory' => ['components', 'subdirectory', ''];
        yield 'subdirectory escaping its directory' => ['components', 'subdirectory', '../../../../etc'];
        yield 'subdirectory escaping with backslashes' => ['controllers', 'subdirectory', '..\\..\\etc'];
        yield 'absolute subdirectory' => ['components', 'subdirectory', '/etc'];
        yield 'current directory as subdirectory' => ['controllers', 'subdirectory', '.'];
        yield 'stream wrapper subdirectory' => ['controllers', 'subdirectory', 'php://filter/resource=ui'];
        yield 'subdirectory with a space' => ['components', 'subdirectory', 'my ui'];
    }

    #[DataProvider('provideInvalidInstallationDirectories')]
    public function testInvalidInstallationDirectoriesAreRejected(string $node, string $option, string $value): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(\sprintf('path "ux_toolkit.%s.%s"', $node, $option));

        $this->processConfiguration([[$node => [$option => $value]]]);
    }

    public function testInstallationDirectoriesAreNormalized(): void
    {
        $config = $this->processConfiguration([[
            'components' => ['dir' => './templates//components/', 'subdirectory' => 'ui/forms/'],
            'controllers' => ['dir' => 'assets/js/./controllers', 'subdirectory' => null],
        ]]);

        $this->assertSame(['dir' => 'templates/components', 'subdirectory' => 'ui/forms'], $config['components']);
        $this->assertSame(['dir' => 'assets/js/controllers', 'subdirectory' => null], $config['controllers']);
    }

    public function testTheLastConfiguredPreviewKitsReplaceThePreviousOnes(): void
    {
        $configuration = new UXToolkitBundle()->getContainerExtension()->getConfiguration([], new ContainerBuilder());

        $config = new Processor()->processConfiguration($configuration, [
            ['preview' => ['kits' => ['shadcn']]],
            ['preview' => ['kits' => ['shadcn', 'common']]],
        ]);

        $this->assertSame(['shadcn', 'common'], $config['preview']['kits']);
    }

    /**
     * @param list<array<string, mixed>> $configs
     *
     * @return array<string, mixed>
     */
    private function processConfiguration(array $configs): array
    {
        $configuration = new UXToolkitBundle()->getContainerExtension()->getConfiguration([], new ContainerBuilder());

        return new Processor()->processConfiguration($configuration, $configs);
    }
}
