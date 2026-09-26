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
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Exception\LogicException;
use Symfony\UX\DesignTokens\Generator\ColorScheme;
use Symfony\UX\DesignTokens\Generator\GeneratorInterface;
use Symfony\UX\DesignTokens\TokenPath;
use Symfony\UX\DesignTokens\TokenRegistryInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:design-tokens:export',
    description: 'Export design tokens to a file (DTCG, CSS, JavaScript)',
)]
final class ExportCommand extends Command
{
    /**
     * Contents are not typed: the locator is fed by a tag, so each service is
     * checked against GeneratorInterface when it is pulled out.
     */
    private readonly Formats $generators;

    /**
     * @param ServiceProviderInterface<mixed> $generators keyed by the "format" tag attribute
     */
    public function __construct(
        private readonly TokenRegistryInterface $tokenRegistry,
        ServiceProviderInterface $generators,
        private readonly string $cssPrefix = 'dt',
        private readonly ColorScheme $colorScheme = new ColorScheme(),
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        $this->generators = new Formats($generators);

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('format', InputArgument::REQUIRED, \sprintf('Output format (%s)', implode(', ', $this->generators->names())))
            ->addArgument('output', InputArgument::OPTIONAL, 'Output file path (stdout if omitted)')
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'Page title, for formats that have one', 'Design System')
            ->addOption('input', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Resolver input as name=value, repeatable')
            ->addOption('all-permutations', null, InputOption::VALUE_NONE, 'Write one file per Resolver permutation, using the output path as a template')
            ->addOption('css-prefix', null, InputOption::VALUE_REQUIRED, 'Application prefix for CSS variables', $this->cssPrefix)
            ->setHelp(<<<'HELP'
                Export the configured design tokens:

                  <info>php bin/console ux:design-tokens:export dtcg build/tokens.tokens.json</info>
                  <info>php bin/console ux:design-tokens:export css assets/styles/theme.css</info>

                When the Resolver declares the color scheme modifier, the CSS holds
                the light context and, for the dark one, only what changes.

                Select another Resolver context with <info>--input</info>:

                  <info>php bin/console ux:design-tokens:export dtcg build/ocean.tokens.json --input=brand=ocean</info>

                Or write every context the Resolver can produce at once. The output
                path gains one suffix per input, so <info>build/theme.css</info> becomes
                <info>build/theme.brand-ocean.scheme-dark.css</info>:

                  <info>php bin/console ux:design-tokens:export css build/theme.css --all-permutations</info>
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $requested */
        $requested = $input->getArgument('format');
        $format = $this->generators->find($requested);

        if (null === $format) {
            $io->error(\sprintf('Unknown format "%s". Available: %s', $requested, implode(', ', $this->generators->names())));

            return Command::INVALID;
        }

        // The locator is fed by a tag, so a wrongly tagged service is reported
        // here instead of surfacing as a TypeError further down.
        $generator = $this->generators->get($format);
        if (!$generator instanceof GeneratorInterface) {
            $io->error(\sprintf('The service registered for the "%s" export format must implement %s.', $format, GeneratorInterface::class));

            return Command::FAILURE;
        }

        $cssPrefix = $input->getOption('css-prefix');
        if (!\is_string($cssPrefix)) {
            $io->error('The --css-prefix option must be a string.');

            return Command::INVALID;
        }

        /** @var string|null $outputPath */
        $outputPath = $input->getArgument('output');
        $everyPermutation = true === $input->getOption('all-permutations');

        try {
            TokenPath::validateCssPrefix($cssPrefix);
            $inputs = $this->parseInputs($input);

            if ($everyPermutation) {
                if (null === $outputPath) {
                    throw new InvalidArgumentException('The --all-permutations option needs an output path to derive file names from.');
                }
                if ([] !== $inputs) {
                    throw new InvalidArgumentException('The --all-permutations and --input options cannot be used together.');
                }

                return $this->exportPermutations($io, $generator, $format, $cssPrefix, $outputPath, $input);
            }

            $result = $this->render($generator, $cssPrefix, $inputs, $input);
        } catch (\InvalidArgumentException|\LogicException|\RuntimeException|\JsonException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if (null === $outputPath) {
            $output->write($result);

            return Command::SUCCESS;
        }

        try {
            $this->filesystem->dumpFile($outputPath, $result);
        } catch (IOExceptionInterface $e) {
            $io->error(\sprintf('Could not write export to "%s": %s', $outputPath, $e->getMessage()));

            return Command::FAILURE;
        }

        $io->success(\sprintf('Exported %s to %s', $format, $outputPath));

        return Command::SUCCESS;
    }

    /**
     * Write one file per Resolver permutation.
     */
    private function exportPermutations(
        SymfonyStyle $io,
        GeneratorInterface $generator,
        string $format,
        string $cssPrefix,
        string $outputPath,
        InputInterface $input,
    ): int {
        $permutations = $this->tokenRegistry->getPermutations();
        if ([] === $permutations) {
            throw new LogicException('Exporting every permutation needs a Resolver document. Configure "ux_design_tokens.resolver.path".');
        }

        // Two contexts such as "a b" and "a-b" reduce to one file name; writing
        // both would silently keep only the last.
        $paths = [];
        foreach ($permutations as $index => $inputs) {
            $path = $this->permutationPath($outputPath, $inputs);
            if (isset($paths[$path])) {
                throw new InvalidArgumentException(\sprintf('The permutations %s and %s would both be written to the same file "%s". Rename one of the contexts.', json_encode($permutations[$paths[$path]], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE), json_encode($inputs, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE), $path));
            }
            $paths[$path] = $index;
        }

        $written = [];
        foreach ($paths as $path => $index) {
            $inputs = $permutations[$index];

            $contents = $this->render($generator, $cssPrefix, $inputs, $input);

            try {
                $this->filesystem->dumpFile($path, $contents);
            } catch (IOExceptionInterface $e) {
                $io->error(\sprintf('Could not write export to "%s": %s', $path, $e->getMessage()));

                return Command::FAILURE;
            }

            $written[] = $path;
        }

        $io->success(\sprintf('Exported %d %s permutations', \count($written), $format));
        $io->listing($written);

        return Command::SUCCESS;
    }

    /**
     * @param array<string, string|int|float> $inputs
     */
    private function render(
        GeneratorInterface $generator,
        string $cssPrefix,
        array $inputs,
        InputInterface $input,
    ): string {
        $title = $input->getOption('title');
        \assert(\is_string($title));

        // Any format may read these: a page title, the prefix of CSS custom
        // properties, and the dark resolution the CSS writes next to the light
        // one. Without a color scheme in the Resolver, there is only one
        // resolution.
        $contexts = $this->colorScheme->contexts($this->tokenRegistry->getModifiers(), $inputs);

        return $generator->generate($this->tokenRegistry->all($contexts[0] ?? $inputs), [
            GeneratorInterface::TITLE => $title,
            GeneratorInterface::CSS_PREFIX => $cssPrefix,
            GeneratorInterface::DARK_TOKENS => null === $contexts ? null : $this->tokenRegistry->all($contexts[1]),
        ]);
    }

    /**
     * Insert the selected inputs before the extension, so several permutations
     * can be written next to each other without overwriting one another.
     *
     * @param array<string, string|int|float> $inputs
     */
    private function permutationPath(string $outputPath, array $inputs): string
    {
        ksort($inputs);
        $parts = [];
        foreach ($inputs as $name => $value) {
            // Names and contexts come from a Resolver document, so they are
            // reduced to a safe segment before they reach a path.
            $parts[] = self::slug($name).'-'.self::slug((string) $value);
        }
        if ([] === $parts) {
            return $outputPath;
        }

        $suffix = '.'.implode('.', $parts);

        // `.tokens.json` and `.resolver.json` carry meaning as a whole, so the
        // suffix goes in front of the pair rather than between its halves.
        foreach (['.tokens.json', '.resolver.json'] as $compound) {
            if (str_ends_with($outputPath, $compound)) {
                return substr($outputPath, 0, -\strlen($compound)).$suffix.$compound;
            }
        }

        $extension = pathinfo($outputPath, \PATHINFO_EXTENSION);

        return '' === $extension
            ? $outputPath.$suffix
            : substr($outputPath, 0, -\strlen($extension) - 1).$suffix.'.'.$extension;
    }

    private static function slug(string $value): string
    {
        $slug = trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $value), '-');

        return '' === $slug ? 'x' : $slug;
    }

    /** @return array<string, string|int|float> */
    private function parseInputs(InputInterface $input): array
    {
        $raw = $input->getOption('input');
        if (!\is_array($raw)) {
            throw new InvalidArgumentException('The --input option must be a list of name=value pairs.');
        }

        $inputs = [];
        foreach ($raw as $pair) {
            if (!\is_string($pair) || !str_contains($pair, '=')) {
                throw new InvalidArgumentException(\sprintf('The --input option expects "name=value", got "%s".', \is_string($pair) ? $pair : get_debug_type($pair)));
            }
            [$name, $value] = explode('=', $pair, 2);
            if ('' === $name) {
                throw new InvalidArgumentException(\sprintf('The --input option expects a non-empty name, got "%s".', $pair));
            }
            $inputs[$name] = $value;
        }

        return $inputs;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('format')) {
            $suggestions->suggestValues($this->generators->names());
        }
        if ($input->mustSuggestOptionValuesFor('input')) {
            $pairs = [];
            foreach ($this->tokenRegistry->getPermutations() as $permutation) {
                foreach ($permutation as $name => $context) {
                    $pairs[$name.'='.$context] = true;
                }
            }
            $suggestions->suggestValues(array_keys($pairs));
        }
    }
}
