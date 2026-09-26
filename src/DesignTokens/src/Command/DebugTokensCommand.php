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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\DesignTokens\Exception\LogicException;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;
use Symfony\UX\DesignTokens\Resolver\ResolverSource;
use Symfony\UX\DesignTokens\Token\TokenInterface;
use Symfony\UX\DesignTokens\TokenRegistryInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
#[AsCommand(
    name: 'debug:design-tokens',
    description: 'Display resolved UX design tokens',
)]
final class DebugTokensCommand extends Command
{
    /**
     * @param array<string, string|int|float> $defaultInputs
     */
    public function __construct(
        private readonly TokenRegistryInterface $tokenRegistry,
        private readonly ?ConfiguredTokenResolver $resolver = null,
        private readonly array $defaultInputs = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::OPTIONAL, 'An exact token path or group prefix')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format (txt or json)', 'txt')
            ->addOption('sources', null, InputOption::VALUE_NONE, 'Show which file set each value, and which ones it replaced')
            ->setHelp(<<<'HELP'
                Display the resolved tokens available through TokenRegistry and Twig:

                  <info>php bin/console debug:design-tokens</info>
                  <info>php bin/console debug:design-tokens color.brand</info>
                  <info>php bin/console debug:design-tokens --format=json</info>

                Several files may set the same path, and the last one wins. <info>--sources</info>
                answers which one that was, and which ones it replaced:

                  <info>php bin/console debug:design-tokens color.action --sources</info>

                Resolving that costs a second pass, so it is off by default.
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format');
        if (!\is_string($format) || !\in_array($format, ['txt', 'json'], true)) {
            new SymfonyStyle($input, $output)->error('The --format option must be one of: txt, json.');

            return Command::INVALID;
        }
        $path = $input->getArgument('path');
        if (null !== $path && !\is_string($path)) {
            new SymfonyStyle($input, $output)->error('The path argument must be a string.');

            return Command::INVALID;
        }
        $withSources = true === $input->getOption('sources');

        try {
            $tokens = $this->flatten($this->tokenRegistry->all());
            $provenance = $withSources ? $this->provenance() : [];
        } catch (\Throwable $error) {
            new SymfonyStyle($input, $output)->error($error->getMessage());

            return Command::FAILURE;
        }

        if (null !== $path) {
            $tokens = array_filter(
                $tokens,
                static fn (TokenInterface $token, string $tokenPath): bool => $tokenPath === $path || str_starts_with($tokenPath, $path.'.'),
                \ARRAY_FILTER_USE_BOTH,
            );
        }
        if ([] === $tokens) {
            new SymfonyStyle($input, $output)->error(null === $path ? 'No design tokens are configured.' : \sprintf('No design tokens found under "%s".', $path));

            return Command::FAILURE;
        }

        if ('json' === $format) {
            $data = [];
            foreach ($tokens as $tokenPath => $token) {
                $data[$tokenPath] = [
                    'type' => $token->getType(),
                    'value' => $token->getValue(),
                    'description' => $token->getDescription(),
                    'deprecated' => $token->getDeprecationMessage() ?? $token->isDeprecated(),
                    'extensions' => $token->getExtensions(),
                ];
                if ($withSources) {
                    $data[$tokenPath] += $provenance[$tokenPath] ?? ['source' => null, 'overrides' => []];
                }
            }
            $output->writeln(json_encode(['tokens' => $data], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR));

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($tokens as $tokenPath => $token) {
            $row = [$tokenPath, $token->getType(), (string) $token];
            if ($withSources) {
                $entry = $provenance[$tokenPath] ?? ['source' => null, 'overrides' => []];
                $row[] = $entry['source'] ?? '';
                $row[] = implode("\n", $entry['overrides']);
            }
            $row[] = $token->getDescription() ?? '';
            $rows[] = $row;
        }
        $headers = $withSources
            ? ['Path', 'Type', 'CSS value', 'Source', 'Replaced', 'Description']
            : ['Path', 'Type', 'CSS value', 'Description'];
        new SymfonyStyle($input, $output)->table($headers, $rows);

        return Command::SUCCESS;
    }

    /** @return array<string, array{source: ?string, overrides: list<string>}> */
    private function provenance(): array
    {
        $resolution = ($this->resolver ?? throw new LogicException('Tracing sources needs the configured token resolver.'))->trace($this->defaultInputs);

        $provenance = [];
        foreach (array_keys($this->flatten($resolution->getTokens())) as $path) {
            $provenance[$path] = [
                'source' => self::label($resolution->getSource($path)),
                'overrides' => array_values(array_filter(array_map(self::label(...), $resolution->getOverrides($path)))),
            ];
        }

        return $provenance;
    }

    private static function label(?ResolverSource $source): ?string
    {
        if (null === $source) {
            return null;
        }

        return $source->uri ?? ('' !== $source->basePath ? $source->basePath : null);
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<string, TokenInterface>
     */
    private function flatten(array $values, string $prefix = ''): array
    {
        $tokens = [];
        foreach ($values as $name => $value) {
            $name = (string) $name;
            $path = '' === $prefix ? $name : $prefix.'.'.$name;
            if ($value instanceof TokenInterface) {
                $tokens[$path] = $value;
            } elseif (\is_array($value)) {
                $tokens += $this->flatten($value, $path);
            }
        }

        return $tokens;
    }
}
