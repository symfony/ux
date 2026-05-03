<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Editor\Bridge\BridgeRegistry;
use Symfony\UX\Editor\Config\Preset\PresetRegistry;
use Symfony\UX\Editor\Content\Converter\ContentConverterRegistry;
use Symfony\UX\Editor\Upload\UploadHandlerRegistry;

#[AsCommand(name: 'debug:ux-editor', description: 'List registered ux-editor bridges, presets, converters, upload handlers')]
final class DebugEditorCommand extends Command
{
    public function __construct(
        private readonly BridgeRegistry $bridges,
        private readonly PresetRegistry $presets,
        private readonly ContentConverterRegistry $converters,
        private readonly UploadHandlerRegistry $uploadHandlers,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Bridges');
        $rows = [];
        foreach ($this->bridges->all() as $id => $b) {
            $rows[] = [$id, $b->getControllerName(), implode(',', $b->getCapabilities()->supportedFormats)];
        }
        $io->table(['ID', 'Controller', 'Formats'], $rows);

        $io->section('Presets');
        $io->listing(array_keys($this->presets->all()) ?: ['(none)']);

        $io->section('Content converters');
        $pairs = $this->converters->pairs();
        $io->listing($pairs === [] ? ['(none)'] : array_map(fn (array $p): string => sprintf('%s -> %s', $p['from'], $p['to']), $pairs));

        $io->section('Upload handlers');
        $io->listing(array_keys($this->uploadHandlers->all()) ?: ['(none)']);

        return Command::SUCCESS;
    }
}
