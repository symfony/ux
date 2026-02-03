<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\File;
use Symfony\UX\Toolkit\Installer\Installer;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Registry\LocalRegistry;
use Symfony\UX\Toolkit\Registry\RegistryFactory;

/**
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:install',
    description: 'Install a new UX Toolkit recipe in your project',
)]
class InstallCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly RegistryFactory $registryFactory,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('recipe', InputArgument::OPTIONAL, 'The recipe name (ex: "button")')
            ->addOption('kit', 'k', InputOption::VALUE_OPTIONAL, 'The kit name (ex: "shadcn", or "github.com/user/my-ux-toolkit-kit")')
            ->addOption(
                'destination',
                'd',
                InputOption::VALUE_OPTIONAL,
                'The destination directory',
                getcwd(),
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the recipe installation, even if the files already exists')
            ->setHelp(
                <<<EOF
                    The <info>%command.name%</info> command will install a new UX Recipe in your project.

                    To install a recipe, use:

                    <info>php %command.full_name% button</info>

                    To install a recipe from a specific Kit (either official or external), use the <info>--kit</info> option:

                    <info>php %command.full_name% button --kit=shadcn</info>
                    <info>php %command.full_name% button --kit=https://github.com/user/my-kit</info>
                    <info>php %command.full_name% button --kit=https://github.com/user/my-kit:branch</info>
                    EOF
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $kitName = $input->getOption('kit');
        $recipeName = $input->getArgument('recipe');

        // If the kit name is not explicitly provided, we need to suggest one
        if (null === $kitName) {
            /** @var list<Kit> $availableKits */
            $availableKits = [];
            $availableKitNames = LocalRegistry::getAvailableKitsName();
            foreach ($availableKitNames as $availableKitName) {
                $kit = $this->registryFactory->getForKit($availableKitName)->getKit($availableKitName);

                if (null === $recipeName) {
                    $availableKits[] = $kit;
                } elseif (null !== $kit->getRecipe(name: $recipeName)) {
                    $availableKits[] = $kit;
                }
            }
            // If more than one kit is available, we ask the user which one to use
            if (($availableKitsCount = \count($availableKits)) > 1) {
                $kitName = $io->choice(null === $recipeName ? 'Which Kit do you want to use?' : \sprintf('The recipe "%s" exists in multiple Kits. Which one do you want to use?', $recipeName), array_map(static fn (Kit $kit) => $kit->manifest->name, $availableKits));

                foreach ($availableKits as $availableKit) {
                    if ($availableKit->manifest->name === $kitName) {
                        $kit = $availableKit;
                        break;
                    }
                }
            } elseif (1 === $availableKitsCount) {
                $kit = $availableKits[0];
            } else {
                if (null === $recipeName) {
                    $io->error('It seems that no official kits are available and it should not happens. Please open an issue on https://github.com/symfony/ux to report this.');
                } else {
                    $io->error(\sprintf('The recipe "%s" does not exist in any official kits.', $recipeName));
                    // $io->error(\sprintf("The recipe \"%s\" does not exist in any official kits.\n\nYou can try to run one of the following commands to interactively install recipes:\n%s\n\nOr you can try one of the community kits https://github.com/search?q=topic:ux-toolkit&type=repositories", $recipeName, implode("\n", array_map(fn (string $availableKitName) => \sprintf('$ bin/console %s --kit %s', $this->getName(), $availableKitName), $availableKitNames))));
                }

                return Command::FAILURE;
            }
        } else {
            $registry = $this->registryFactory->getForKit($kitName);
            $kit = $registry->getKit($kitName);
        }

        if (null === $recipeName) {
            // Ask for the recipe name if not provided
            $recipeName = $io->choice('Which recipe do you want to install?', array_map(static fn (Recipe $recipe) => $recipe->manifest->name, $kit->getRecipes()));
            $recipe = $kit->getRecipe(name: $recipeName);
        } elseif (null === $recipe = $kit->getRecipe($recipeName)) {
            // Suggest alternatives if recipe does not exist
            $message = \sprintf('The recipe "%s" does not exist.', $recipeName);

            $alternativeRecipes = $this->getAlternativeRecipes($kit, $recipeName);
            $alternativeRecipesCount = \count($alternativeRecipes);

            if (1 === $alternativeRecipesCount && $input->isInteractive()) {
                $io->warning($message);
                if ($io->confirm(\sprintf('Do you want to install the recipe "%s" instead?', $alternativeRecipes[0]->name))) {
                    $recipe = $alternativeRecipes[0];
                } else {
                    return Command::FAILURE;
                }
            } elseif ($alternativeRecipesCount > 0) {
                $io->warning(\sprintf('%s'."\n".'Possible alternatives: "%s"', $message, implode('", "', array_map(static fn (Recipe $r) => $r->name, $alternativeRecipes))));

                return Command::FAILURE;
            } else {
                $io->error($message);

                return Command::FAILURE;
            }
        }

        $io->writeln(\sprintf('Installing recipe "<info>%s</>" from the <info>%s</> kit...', $recipe->name, $kit->manifest->name));

        $installer = new Installer($this->filesystem, fn (string $question) => $this->io->confirm($question, $input->isInteractive()));
        $installationReport = $installer->installRecipe($kit, $recipe, $destinationPath = $input->getOption('destination'), $input->getOption('force'));

        if ([] === $installationReport->newFiles) {
            $this->io->warning('The recipe has not been installed.');

            return Command::SUCCESS;
        }

        $this->io->success('The recipe has been installed.');

        $this->io->section('Installed files');
        $this->io->listing(array_map(static fn (File $file) => Path::join($destinationPath, $file->sourceRelativePathName), $installationReport->newFiles));

        if ([] !== $installationReport->suggestedPhpPackages || [] !== $installationReport->suggestedNpmPackages || [] !== $installationReport->suggestedImportmapPackages) {
            $this->io->section('Next steps');
        }

        $stepIndex = 0;
        if ([] !== $installationReport->suggestedPhpPackages) {
            $this->io->writeln(++$stepIndex.'. Install suggested PHP package(s) with the command:');
            $this->io->newLine();
            $this->io->writeln(\sprintf(' $ <info>composer require %s</>', implode(' ', $installationReport->suggestedPhpPackages)));
            $this->io->newLine();
        }

        if ([] !== $installationReport->suggestedNpmPackages && [] !== $installationReport->suggestedImportmapPackages) {
            $this->io->writeln(++$stepIndex.'. Install suggested front-end packages with one of the following commands:');
            $this->io->newLine();
            $this->io->writeln(' # with npm/pnpm/yarn');
            $this->io->writeln(\sprintf(' $ <info>npm install --save %s</>', implode(' ', $installationReport->suggestedNpmPackages)));
            $this->io->newLine();
            $this->io->writeln(' # or with Importmap');
            $this->io->writeln(\sprintf(' $ <info>php bin/console importmap:install %s</>', implode(' ', $installationReport->suggestedImportmapPackages)));
            $this->io->newLine();
        } elseif ([] !== $installationReport->suggestedNpmPackages) {
            $this->io->writeln(++$stepIndex.'. Install suggested front-end package(s) with the command:');
            $this->io->newLine();
            $this->io->writeln(\sprintf(' $ <info>npm install --save %s</>', implode(' ', $installationReport->suggestedNpmPackages)));
            $this->io->newLine();
        } elseif ([] !== $installationReport->suggestedImportmapPackages) {
            $this->io->writeln(++$stepIndex.'. Install suggested front-end package(s) with the command:');
            $this->io->newLine();
            $this->io->writeln(\sprintf(' $ <info>php bin/console importmap:install %s</>', implode(' ', $installationReport->suggestedImportmapPackages)));
            $this->io->newLine();
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<Recipe>
     */
    private function getAlternativeRecipes(Kit $kit, string $recipeName): array
    {
        $alternativeRecipes = [];

        foreach ($kit->getRecipes() as $recipe) {
            $lev = levenshtein($recipeName, $recipe->name, 2, 5, 10);
            if ($lev <= 8 || str_contains($recipe->name, $recipeName)) {
                $alternativeRecipes[] = $recipe;
            }
        }

        usort($alternativeRecipes, static fn (Recipe $recipeA, Recipe $recipeB) => strcmp($recipeA->name, $recipeB->name));

        return $alternativeRecipes;
    }
}
