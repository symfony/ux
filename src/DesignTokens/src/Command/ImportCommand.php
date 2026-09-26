<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Command;

use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Exception\RuntimeException;
use Symfony\UX\DesignTokens\Importer\ImporterInterface;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:design-tokens:import',
    description: 'Import design tokens from another format into DTCG 2025.10',
)]
final class ImportCommand extends Command
{
    /**
     * @param ServiceProviderInterface<mixed> $importers keyed by the "format" tag attribute
     */
    public function __construct(
        private readonly ServiceProviderInterface $importers,
        private readonly TokenTreeBuilder $treeBuilder,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('format', InputArgument::REQUIRED, \sprintf('Input format (%s)', implode(', ', $this->formats())))
            ->addArgument('input', InputArgument::REQUIRED, 'Input file path')
            ->addArgument('output', InputArgument::OPTIONAL, 'DTCG output file path (stdout if omitted)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Replace the output file if it exists')
            ->setHelp(<<<'HELP'
                Convert a document of another format into a DTCG document:

                  <info>php bin/console ux:design-tokens:import tailwind assets/styles/theme.css design/theme.tokens.json</info>

                What DTCG cannot represent is left out. A value it cannot hold is
                reported as a warning; an entry it has no type for is listed with
                <info>-v</info>, and every detail of the parse with <info>-vvv</info>.
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $format = $input->getArgument('format');
        if (!\is_string($format)) {
            $io->error('The import format must be a string.');

            return Command::INVALID;
        }
        if (!$this->importers->has($format)) {
            $io->error(\sprintf('Unknown import format "%s". Available: %s', $format, implode(', ', $this->formats())));

            return Command::INVALID;
        }

        $importer = $this->importers->get($format);
        if (!$importer instanceof ImporterInterface) {
            $io->error(\sprintf('The service registered for the "%s" import format must implement %s.', $format, ImporterInterface::class));

            return Command::FAILURE;
        }

        // Log to stderr, so a document written to stdout stays valid JSON.
        if ($importer instanceof LoggerAwareInterface) {
            $importer->setLogger(new ConsoleLogger($output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output));
        }

        try {
            $inputPath = $input->getArgument('input');
            if (!\is_string($inputPath)) {
                throw new InvalidArgumentException('The import input path must be a string.');
            }
            $tokens = $importer->import($this->read($inputPath));
            $this->treeBuilder->resolve($tokens);
            $json = json_encode($tokens, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR)."\n";
        } catch (\Throwable $error) {
            $io->error($error->getMessage());

            return Command::FAILURE;
        }

        $outputPath = $input->getArgument('output');
        if (null === $outputPath) {
            $output->write($json);

            return Command::SUCCESS;
        }
        if (!\is_string($outputPath)) {
            $io->error('The import output path must be a string.');

            return Command::INVALID;
        }
        if (is_file($outputPath) && true !== $input->getOption('force')) {
            $io->error(\sprintf('"%s" already exists. Pass --force to replace it.', $outputPath));

            return Command::FAILURE;
        }

        try {
            $this->filesystem->dumpFile($outputPath, $json);
        } catch (IOExceptionInterface $e) {
            $io->error(\sprintf('Could not write imported tokens to "%s": %s', $outputPath, $e->getMessage()));

            return Command::FAILURE;
        }

        $io->success(\sprintf('Imported %s tokens to %s', $format, $outputPath));

        return Command::SUCCESS;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('format')) {
            $suggestions->suggestValues($this->formats());
        }
    }

    private function read(string $path): string
    {
        if (!is_file($path)) {
            throw new RuntimeException(\sprintf('Input file not found: "%s".', $path));
        }

        try {
            return $this->filesystem->readFile($path);
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException(\sprintf('Could not read input file "%s".', $path), previous: $e);
        }
    }

    /** @return list<string> */
    private function formats(): array
    {
        return array_map(strval(...), array_keys($this->importers->getProvidedServices()));
    }
}
