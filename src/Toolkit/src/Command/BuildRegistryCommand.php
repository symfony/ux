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
use Symfony\Component\Finder\Finder;
use Symfony\UX\Toolkit\Compiler\RegistryCompiler;
use Symfony\UX\Toolkit\Registry\Registry;
use Symfony\UX\Toolkit\Registry\RegistryItemFactory;

/**
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:toolkit:build-registry',
    description: 'This command allows to distribute your components, and build the registry.'
)]
class BuildRegistryCommand extends Command
{
    public function __construct(
        private readonly RegistryCompiler $compiler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'template-directory',
                't',
                InputOption::VALUE_REQUIRED,
                'The directory where the templates are stored.',
                'templates'
            )
            ->addOption(
                'destination',
                'r',
                InputOption::VALUE_REQUIRED,
                'The directory where the registry will be stored.',
                'registry'
            )
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the component.', '')
            ->addArgument('homepage', InputArgument::OPTIONAL, 'The homepage of the component.', 'https://...')
            ->addOption(
                'authors',
                '',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The authors of the component.',
                []
            )
            ->addOption(
                'licenses',
                '',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The licenses of the component.',
                []
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Building the registry...');

        $registry = Registry::empty();
        $registry->setName($input->getArgument('name'));
        $registry->setHomepage($input->getArgument('homepage'));

        foreach ($input->getOption('authors') as $author) {
            // author is a string like "name <email>"
            $email = null;
            if (preg_match('/^(.+) <(.+)>$/', $author, $matches)) {
                $author = ['name' => $matches[1], 'email' => $matches[2]];
            }
            $registry->addAuthor($author, $email);
        }

        foreach ($input->getOption('licenses') as $license) {
            $registry->addLicense($license);
        }

        $finderTemplates = Finder::create()
            ->files()
            ->in($input->getOption('template-directory'))
            ->name('*.html.twig')
            ->sortByName();

        $table = [];
        foreach ($finderTemplates as $file) {
            $table[] = [$file->getRelativePathname()];
            $registry->add(RegistryItemFactory::fromTwigFile($file));
        }

        $registryDir = $input->getOption('destination');
        $this->compiler->compile($registry, $registryDir);

        $io->table(['Templates'], $table);

        $io->success('The registry has been successfully built.');

        return Command::SUCCESS;
    }
}
