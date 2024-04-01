<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\Iconify;
use Symfony\UX\Icons\Registry\LocalSvgIconRegistry;
use Symfony\UX\Icons\Twig\IconFinder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:icons:lock',
    description: 'Scan project and import icon(s) from iconify.design',
)]
final class LockIconsCommand extends Command
{
    public function __construct(
        private Iconify $iconify,
        private LocalSvgIconRegistry $registry,
        private IconFinder $iconFinder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'force',
                mode: InputOption::VALUE_NONE,
                description: 'Force re-import of all found icons'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        $count = 0;

        $io->comment('Scanning project for icons...');

        foreach ($this->iconFinder->icons() as $icon) {
            if (2 !== \count($parts = explode(':', $icon))) {
                continue;
            }

            if (!$force && $this->registry->has($icon)) {
                // icon already imported
                continue;
            }

            [$prefix, $name] = $parts;

            try {
                $svg = $this->iconify->fetchSvg($prefix, $name);
            } catch (IconNotFoundException) {
                // icon not found on iconify
                continue;
            }

            $this->registry->add(sprintf('%s/%s', $prefix, $name), $svg);

            $license = $this->iconify->metadataFor($prefix)['license'];
            ++$count;

            $io->text(sprintf(
                " <fg=bright-green;options=bold>✓</> Imported <fg=bright-white;bg=black>%s:</><fg=bright-magenta;bg=black;options>%s</> (License: <href=%s>%s</>). Render with: <comment>{{ ux_icon('%s') }}</comment>",
                $prefix,
                $name,
                $license['url'],
                $license['title'],
                $icon,
            ));
        }

        $io->success(sprintf('Imported %d icons.', $count));

        return Command::SUCCESS;
    }
}
