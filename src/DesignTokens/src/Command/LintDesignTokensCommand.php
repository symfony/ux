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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\CI\GithubActionReporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\DesignTokens\Exception\RuntimeException;
use Symfony\UX\DesignTokens\Exception\UnresolvedReferenceException;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\ResolverDocument;
use Symfony\UX\DesignTokens\Token\TokenInterface;
use Symfony\UX\DesignTokens\TokenRegistryInterface;
use Symfony\UX\DesignTokens\Validation\DtcgValidator;
use Symfony\UX\DesignTokens\Validation\Normalizer;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
#[AsCommand(
    name: 'lint:design-tokens',
    description: 'Lint and normalize DTCG 2025.10 token and Resolver documents',
)]
final class LintDesignTokensCommand extends Command
{
    /**
     * @param list<string> $configuredPaths
     */
    public function __construct(
        private readonly DtcgValidator $validator,
        private readonly Normalizer $normalizer,
        private readonly ?TokenRegistryInterface $tokenRegistry = null,
        private readonly array $configuredPaths = [],
        private readonly ?string $configuredResolverPath = null,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'A file, directory, or "-" for STDIN')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Rewrite each document in its normalized form')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format (txt, json, or github; github by default on GitHub Actions)')
            ->setHelp(<<<'HELP'
                Lint DTCG 2025.10 token and Resolver documents:

                  <info>php bin/console lint:design-tokens design/theme.resolver.json</info>
                  <info>php bin/console lint:design-tokens theme.tokens.json --format=json</info>

                Without filenames, the command lints the files configured under
                <info>ux_design_tokens.paths</info> and <info>resolver.path</info>, then checks that
                the application's own resolution succeeds:

                  <info>php bin/console lint:design-tokens</info>

                A token document that a Resolver of the same run uses may alias tokens
                other sources define; the Resolver checks those references:

                  <info>php bin/console lint:design-tokens design/</info>

                <info>--fix</info> rewrites each document in canonical form, in place, preserving
                references, group metadata, empty objects and author-defined entry
                order. Gate it in CI the way any formatter is gated, by running it and
                then checking the working tree is clean:

                  <info>php bin/console lint:design-tokens design/base.tokens.json --fix</info>
                  <info>cat theme.tokens.json | php bin/console lint:design-tokens - --fix</info>
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format') ?? (GithubActionReporter::isGithubActionEnvironment() ? 'github' : 'txt');
        if (!\is_string($format) || !\in_array($format, ['txt', 'json', 'github'], true)) {
            new SymfonyStyle($input, $output)->error('The --format option must be one of: txt, json, github.');

            return Command::INVALID;
        }

        $argument = $input->getArgument('filename');
        if (!\is_array($argument)) {
            new SymfonyStyle($input, $output)->error('The filename argument must be a list.');

            return Command::INVALID;
        }
        $fix = true === $input->getOption('fix');

        $requested = array_values(array_filter($argument, \is_string(...)));
        $configured = [] === $requested;
        if ($configured) {
            $requested = $this->configuredPaths;
            if (null !== $this->configuredResolverPath) {
                $requested[] = $this->configuredResolverPath;
            }
        }
        if ([] === $requested) {
            new SymfonyStyle($input, $output)->error('No design token files were provided or configured.');

            return Command::INVALID;
        }

        try {
            $files = $this->expand($requested);
        } catch (\Throwable $error) {
            return $this->render($format, [['file' => '', 'valid' => false, 'error' => $error->getMessage(), 'warnings' => [], 'fixed' => false]], null, $input, $output);
        }

        $completed = $this->completedByResolvers($files);
        $results = [];
        foreach ($files as $file) {
            $results[] = $this->lint($file, $fix, isset($completed[(string) realpath($file)]), $input, $output);
        }

        // Report on stderr when the fixed document goes to stdout.
        $report = $fix && \in_array('-', $files, true) && $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        return $this->render($format, $results, $configured ? $this->resolution() : null, $input, $report);
    }

    /**
     * @return array{file: string, valid: bool, error: ?string, warnings: list<string>, fixed: bool}
     */
    private function lint(string $file, bool $fix, bool $partial, InputInterface $input, OutputInterface $output): array
    {
        $result = ['file' => $file, 'valid' => true, 'error' => null, 'warnings' => [], 'fixed' => false];

        try {
            if ('-' === $file) {
                $stream = $input instanceof StreamableInputInterface ? $input->getStream() : null;
                $source = stream_get_contents($stream ?? \STDIN);
                if (false === $source || '' === trim($source)) {
                    throw new RuntimeException('Could not read a DTCG document from standard input.');
                }
                $result['warnings'] = $this->validator->validateJson($source);
                $normalized = $fix ? $this->normalizer->normalize($source, 'stdin', (string) getcwd()) : $source;
            } else {
                try {
                    $source = new Filesystem()->readFile($file);
                } catch (IOExceptionInterface $e) {
                    throw new RuntimeException(\sprintf('Could not read design token document: "%s".', $file), previous: $e);
                }
                $result['warnings'] = $this->validator->validateFile($file, $partial);
                $normalized = $fix ? $this->normalizer->normalizeFile($file, $partial) : $source;
            }
        } catch (UnresolvedReferenceException $error) {
            return [...$result, 'valid' => false, 'error' => $error->getMessage().' If a Resolver completes this document with other sources, lint it with that Resolver.', 'warnings' => []];
        } catch (\Throwable $error) {
            return [...$result, 'valid' => false, 'error' => $error->getMessage(), 'warnings' => []];
        }

        if ($fix && '-' === $file) {
            $output->write($normalized);

            return [...$result, 'fixed' => $source !== $normalized];
        }

        if (!$fix || $source === $normalized) {
            return $result;
        }

        try {
            $this->filesystem->dumpFile($file, $normalized);
        } catch (IOExceptionInterface $error) {
            return [...$result, 'valid' => false, 'error' => \sprintf('Could not write the normalized document: %s', $error->getMessage()), 'warnings' => []];
        }

        return [...$result, 'fixed' => true];
    }

    /**
     * @param list<string> $files
     *
     * @return array<string, true> keyed by real path
     */
    private function completedByResolvers(array $files): array
    {
        $completed = [];
        foreach ($files as $file) {
            if (!str_ends_with($file, '.resolver.json')) {
                continue;
            }
            try {
                $document = new ResolverDocument(new JsonDocumentLoader()->load($file), \dirname($file));
                foreach ($document->getPermutations() as $inputs) {
                    $document->sourceDescriptors($inputs);
                }
            } catch (\Throwable) {
                continue;
            }
            foreach ($document->loadedUris() as $uri) {
                if (false !== $path = realpath($uri)) {
                    $completed[$path] = true;
                }
            }
        }

        return $completed;
    }

    /** @return array{valid: bool, tokens: int, error: ?string}|null */
    private function resolution(): ?array
    {
        if (null === $this->tokenRegistry) {
            return null;
        }

        try {
            return ['valid' => true, 'tokens' => $this->countTokens($this->tokenRegistry->all()), 'error' => null];
        } catch (\Throwable $error) {
            return ['valid' => false, 'tokens' => 0, 'error' => $error->getMessage()];
        }
    }

    /** @param array<array-key, mixed> $values */
    private function countTokens(array $values): int
    {
        $count = 0;
        foreach ($values as $value) {
            if ($value instanceof TokenInterface) {
                ++$count;
            } elseif (\is_array($value)) {
                $count += $this->countTokens($value);
            }
        }

        return $count;
    }

    /**
     * @param list<string> $requested
     *
     * @return list<string>
     */
    private function expand(array $requested): array
    {
        $files = [];
        foreach ($requested as $path) {
            if ('-' === $path || is_file($path)) {
                $files[$path] = true;
                continue;
            }
            if (!is_dir($path)) {
                throw new RuntimeException(\sprintf('Design token path not found: "%s".', $path));
            }
            foreach ($this->filesInDirectory($path) as $filename) {
                $files[$filename] = true;
            }
        }
        $paths = array_keys($files);
        sort($paths);
        if ([] === $paths) {
            throw new RuntimeException('No .tokens.json, .tokens, or .resolver.json files were found.');
        }

        return $paths;
    }

    /** @return iterable<string> */
    private function filesInDirectory(string $path): iterable
    {
        $directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        for ($directory->rewind(); $directory->valid(); $directory->next()) {
            $filename = $directory->getPathname();
            if ($directory->isDir()) {
                yield from $this->filesInDirectory($filename);
            } elseif ($directory->isFile() && $this->supports($filename)) {
                yield $filename;
            }
        }
    }

    private function supports(string $path): bool
    {
        return str_ends_with($path, '.tokens.json')
            || str_ends_with($path, '.tokens')
            || str_ends_with($path, '.resolver.json');
    }

    /**
     * @param list<array{file: string, valid: bool, error: ?string, warnings: list<string>, fixed: bool}> $results
     * @param array{valid: bool, tokens: int, error: ?string}|null                                        $resolution
     */
    private function render(string $format, array $results, ?array $resolution, InputInterface $input, OutputInterface $output): int
    {
        $valid = !\in_array(false, array_column($results, 'valid'), true)
            && (null === $resolution || $resolution['valid']);
        $warningCount = array_sum(array_map('\count', array_column($results, 'warnings')));
        $fixed = array_values(array_filter($results, static fn (array $result): bool => $result['fixed'] && '-' !== $result['file']));

        if ('json' === $format) {
            $payload = ['valid' => $valid, 'files' => $results];
            if (null !== $resolution) {
                $payload['resolution'] = $resolution;
            }
            $output->writeln(json_encode($payload, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR));

            return $valid ? Command::SUCCESS : Command::FAILURE;
        }

        if ('github' === $format) {
            $reporter = new GithubActionReporter($output);
            foreach ($results as $result) {
                if (!$result['valid']) {
                    $reporter->error($result['error'] ?? 'Invalid DTCG document.', '' === $result['file'] ? null : $result['file'], 1);
                }
                foreach ($result['warnings'] as $warning) {
                    $reporter->warning($warning, '' === $result['file'] ? null : $result['file'], 1);
                }
            }
            if (null !== $resolution && !$resolution['valid']) {
                $reporter->error($resolution['error'] ?? 'The configured design tokens do not resolve.');
            }

            return $valid ? Command::SUCCESS : Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        foreach ($results as $result) {
            foreach ($result['warnings'] as $warning) {
                $io->warning(\sprintf('%s: %s', '' === $result['file'] ? 'Input' : $result['file'], $warning));
            }
        }
        foreach ($fixed as $result) {
            $io->writeln(\sprintf('Normalized %s', $result['file']));
        }

        if (!$valid) {
            foreach ($results as $result) {
                if (!$result['valid']) {
                    $io->error(\sprintf('%s: %s', '' === $result['file'] ? 'Input' : $result['file'], $result['error']));
                }
            }
            if (null !== $resolution && !$resolution['valid']) {
                $io->error(\sprintf('The configured design tokens do not resolve: %s', $resolution['error']));
            }

            return Command::FAILURE;
        }

        $summary = 1 === \count($results)
            ? 'The design token document is valid.'
            : \sprintf('All %d design token documents are valid.', \count($results));
        if (0 !== $warningCount) {
            $summary = \sprintf('%s %d warning%s.', $summary, $warningCount, 1 === $warningCount ? '' : 's');
        }
        if (null !== $resolution) {
            $summary = \sprintf('%s The configured resolution holds %d token%s.', $summary, $resolution['tokens'], 1 === $resolution['tokens'] ? '' : 's');
        }
        $io->success($summary);

        return Command::SUCCESS;
    }
}
