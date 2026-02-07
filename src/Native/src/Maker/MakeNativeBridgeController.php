<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Imad Zairig <imadzairig@gmail.com>
 */
final class MakeNativeBridgeController extends AbstractMaker
{
    private const REQUIRED_PACKAGES = [
        '@symfony/stimulus-bundle',
        '@hotwired/hotwire-native-bridge',
    ];

    public function __construct(
        private string $projectDir,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:native-bridge-controller';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new Native Bridge controller';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the controller (e.g. <fg=yellow>bridge</>)')
            ->setHelp(<<<'EOT'
                The <info>%command.name%</info> command creates a new Hotwire Native Bridge Stimulus controller.

                <info>php %command.full_name% ContactForm</info>

                This will create a new <info>contact_form_controller.js</info> file in your <info>assets/controllers</info> directory
                that extends the Hotwire Native Bridge component.

                <info>php %command.full_name%</info> (interactive)
                EOT
            );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $this->ensurePackagesExist($io);

        $controllerName = $input->getArgument('name');
        if (!$controllerName) {
            $controllerName = $io->ask('What name do you want for your Hotwire Native Bridge controller?', 'bridge', static function ($name) {
                if (!$name) {
                    throw new RuntimeException('Controller name cannot be empty.');
                }

                return $name;
            });
        }
        $controllerName = Str::asSnakeCase($controllerName);

        $controllerPath = \sprintf('%s/assets/controllers/%s_controller.js', $this->projectDir, $controllerName);

        if (file_exists($controllerPath) && !$io->confirm(\sprintf('The controller "%s" already exists. Overwrite it?', $controllerName), false)) {
            $io->comment('Command aborted.');

            return;
        }

        $generator->generateFile(
            $controllerPath,
            __DIR__.'/../Resources/skeleton/bridge_controller.tpl.php',
            ['name' => $controllerName]
        );

        $generator->writeChanges();

        $io->success('Hotwire Native Bridge controller created successfully!');
        $io->text([
            'Next: Add the data-controller attribute to your HTML element:',
            \sprintf('<info>data-controller="%s"</info>', str_replace('_', '-', $controllerName)),
            '',
            'Read the documentation: https://native.hotwired.dev/reference/bridge-installation',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }
}
