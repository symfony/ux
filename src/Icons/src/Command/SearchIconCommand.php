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
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Icons\Iconify;

/**
 * A console command to search icons and icon sets from ux.symfony.com.
 *
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:icons:search',
    description: 'Search icons and icon sets from ux.symfony.com',
)]
final class SearchIconCommand extends Command
{
    public function __construct(private Iconify $iconify)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('prefix', InputArgument::REQUIRED, 'Prefix or name of the icon set (ex: bootstrap, fa, tabler)')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the icon (leave empty to search for sets)')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command search icon sets and icons from ux.symfony.com

To search for <comment>icon sets</comment>, pass the prefix or name of the icon set (or a part of it):

  <info>php %command.full_name% bootstrap</info>
  <info>php %command.full_name% material</info>

To search for <comment>icons</comment>, pass the prefix of the icon set and the name of the icon:

  <info>php %command.full_name% bootstrap star</info>

EOF
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null === $input->getArgument('prefix')) {
            $io = new SymfonyStyle($input, $output);
            if ($prefix = $io->ask('Prefix or name of the icon set (ex: bootstrap, fa, tabler)')) {
                $input->setArgument('prefix', $prefix);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $prefix = $input->getArgument('prefix');
        $name = $input->getArgument('name');

        $iconSets = $this->findIconSets($prefix);

        if (null === $name) {
            $this->renderIconSetTable($io, $iconSets);
            if (1 === \count($iconSets)) {
                $iconSet = reset($iconSets);
                $searchTerm = 'arrow';
                $io->writeln(sprintf('Search <comment>"%s"</comment> in <comment>%s</comment> icons:', $searchTerm, $iconSet['name']));
                $io->newLine();
                $io->writeln(' '.sprintf('php bin/console <comment>ux:icons:search %s %s</comment>', $iconSet['prefix'], $searchTerm));
                $io->newLine();
            }

            return Command::SUCCESS;
        }

        if (false === $iconSet = reset($iconSets)) {
            $io->error(sprintf('No icon sets found for prefix "%s".', $prefix));

            return Command::INVALID;
        }

        if (1 < \count($iconSets) && $prefix !== $iconSet['prefix']) {
            $choices = array_combine(array_keys($iconSets), array_column($iconSets, 'name'));
            $choice = $io->choice('Select an icon set', array_values($choices));
            if (!$choice || false === $prefix = array_search($choice, $choices, true)) {
                $io->error('No icon set selected.');

                return Command::INVALID;
            }
            $iconSet = $iconSets[$prefix];
        }

        $io->write(sprintf('Searching <comment>%s</comment> icons "<comment>%s</comment>"...', $iconSet['name'], $name));
        try {
            $results = $this->iconify->searchIcons($prefix, $name);
        } catch (\Throwable $e) {
            $io->write(' <fg=bright-red;options=bold>✗</>');
            $io->error('An error occurred while searching for icons.');
            if ($io->isVerbose()) {
                $io->writeln($e->getMessage());
                if ($io->isDebug()) {
                    $io->writeln($e->getTraceAsString());
                }
            }

            return Command::FAILURE;
        }
        $io->newLine();

        $icons = $results['icons'] ?? [];
        sort($icons);
        $iconPages = array_chunk($icons, 24);
        $nbPages = \count($iconPages);

        $cursor = new Cursor($output);
        $io->writeln(sprintf('Found <info>%d</info> icons.', \count($icons)));
        foreach ($iconPages as $page => $iconPage) {
            $this->renderIconTable($io, $prefix, $name, $iconPage);
            if ($page + 1 === $nbPages) {
                break;
            }
            if (!$io->confirm(sprintf('Page <comment>%d</comment>/<comment>%d</comment>. Continue?', $page + 1, $nbPages))) {
                break;
            }
            $cursor->moveUp(5)->clearLineAfter();
        }
        $io->newLine();
        $io->writeln(sprintf('See all the <comment>%s</comment> icons on: https://ux.symfony.com/icons?set=%s', $prefix, $prefix));
        $io->newLine();

        return Command::SUCCESS;
    }

    private function renderIconTable(SymfonyStyle $io, string $prefix, string $name, array $icons): void
    {
        $table = new Table($io);
        $table->setStyle('symfony-style-guide');
        $table->setColumnWidths([40, 40]);
        foreach ($icons as $i => $icon) {
            $icons[$i] = str_replace($name, '<fg=bright-blue>'.$name.'</>', $icon);
        }
        $table->addRows(array_chunk($icons, 2));
        $table->render();
    }

    private function renderIconSetTable(SymfonyStyle $io, array $iconSets): void
    {
        $results = [];
        foreach ($iconSets as $prefix => $iconSet) {
            $results[] = [
                $iconSet['name'] ?? $prefix,
                new TableCell($iconSet['total'], [
                    'style' => new TableCellStyle(['align' => 'right']),
                ]),
                $iconSet['license']['title'] ?? '',
                $iconSet['prefix'],
                $this->formatIcon($io, $prefix.':'.$iconSet['samples'][0], false),
            ];
        }
        $io->table(['Icon set', 'Icons', 'License', 'Prefix', 'Example'], $results);
    }

    private function findIconSets(string $query): array
    {
        $iconSets = [];
        $query = mb_strtolower($query);
        foreach ($this->iconify->getIconSets() as $prefix => $iconSet) {
            if (!str_contains($prefix, $query) && !str_contains(mb_strtolower($iconSet['name']), $query)) {
                continue;
            }
            $iconSets[$prefix] = [...$iconSet, 'prefix' => $prefix];
        }

        return $iconSets;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if (!$input->mustSuggestArgumentValuesFor('prefix')) {
            return;
        }

        $prefixes = array_keys($this->iconify->getIconSets());
        if ($input->getArgument('prefix')) {
            $prefixes = array_filter($prefixes, fn ($prefix) => str_contains($prefix, $input->getArgument('prefix')));
        }

        $suggestions->suggestValues($prefixes);
    }

    private function formatIcon(OutputInterface $output, string $icon, bool $padding = true): string
    {
        if (!$output->getFormatter()->hasStyle('icon-prefix')) {
            $output->getFormatter()->setStyle('icon-prefix', new OutputFormatterStyle('bright-white', 'black'));
        }
        if (!$output->getFormatter()->hasStyle('icon-name')) {
            $output->getFormatter()->setStyle('icon-name', new OutputFormatterStyle('bright-magenta', 'black'));
        }

        [$prefix, $name] = explode(':', $icon.':');
        $padding = $padding ? ' ' : '';

        return sprintf('<icon-prefix>%s%s:</><icon-name>%s%s</>', $padding, $prefix, $name, $padding);
    }
}
