<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit\Lint\Checker;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\UX\Toolkit\Component\Block;
use Symfony\UX\Toolkit\Component\PhpStanTypeValidator;
use Symfony\UX\Toolkit\Component\Prop;
use Symfony\UX\Toolkit\Component\PropsDeclarationParser;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * Normalizes the `{# @prop ... #}` and `{# @block ... #}` docblocks of every component template:
 *  - the line format itself (name, valid PHPDoc type for props, capitalized description ending
 *    with a period);
 *  - their consistency with the template: props against the `{%- props -%}` declaration, blocks
 *    against the blocks actually rendered (`{% block %}`, `block(outerBlocks.x)`, `block('x')`).
 *
 * Default values are intentionally not documented in the docblock: they live only in the
 * `{%- props -%}` declaration, which is the single source of truth.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ComponentDocChecker implements KitCheckerInterface
{
    private const RE_PROP = '/\{#\s*@prop\s+(?P<body>.*?)\s*#\}/s';
    private const RE_BLOCK = '/\{#\s*@block\s+(?P<body>.*?)\s*#\}/s';

    /**
     * The ways a block can be rendered in a Twig component: a `{% block x %}` tag, a forwarded
     * `block(outerBlocks.x)`, or a `block('x')` call.
     */
    private const RE_USED_BLOCKS = [
        '/\{%-?\s*block\s+(?P<name>[a-zA-Z_]\w*)/',
        '/block\(\s*outerBlocks\.(?P<name>[a-zA-Z_]\w*)/',
        '/block\(\s*[\'"](?P<name>[a-zA-Z_]\w*)[\'"]/',
    ];

    private readonly PropsDeclarationParser $propsDeclarationParser;
    private readonly PhpStanTypeValidator $typeValidator;

    public function __construct()
    {
        $this->propsDeclarationParser = new PropsDeclarationParser();
        $this->typeValidator = new PhpStanTypeValidator();
    }

    public function check(Kit $kit): iterable
    {
        foreach ($kit->getRecipes() as $recipe) {
            $templatesDir = Path::join($recipe->absolutePath, 'templates');
            if (!is_dir($templatesDir)) {
                continue;
            }

            $finder = new Finder()->in($templatesDir)->files()->name('*.html.twig')->sortByName();
            foreach ($finder as $file) {
                yield from $this->checkFile($recipe->name, $file->getRelativePathname(), $file->getContents());
            }
        }
    }

    /**
     * @return iterable<LintIssue>
     */
    private function checkFile(string $recipe, string $file, string $contents): iterable
    {
        preg_match_all(self::RE_PROP, $contents, $propMatches, \PREG_SET_ORDER);
        preg_match_all(self::RE_BLOCK, $contents, $blockMatches, \PREG_SET_ORDER);
        $declaration = $this->propsDeclarationParser->parse($contents);
        $usedBlocks = $this->extractUsedBlocks($contents);

        // Nothing documented, declared or rendered: nothing to check (e.g. a static partial).
        if ([] === $propMatches && [] === $blockMatches && null === $declaration && [] === $usedBlocks) {
            return;
        }

        yield from $this->checkProps($recipe, $file, $propMatches, $declaration);
        yield from $this->checkBlocks($recipe, $file, $blockMatches, $usedBlocks);
    }

    /**
     * @param list<array{body: string}>                           $matches
     * @param \Symfony\UX\Toolkit\Component\PropsDeclaration|null $declaration
     *
     * @return iterable<LintIssue>
     */
    private function checkProps(string $recipe, string $file, array $matches, $declaration): iterable
    {
        $documentedNames = [];

        foreach ($matches as $match) {
            $prop = $this->parsePropBody($match['body']);
            if (null === $prop) {
                yield $this->issue($recipe, $file, 'component.prop.invalid', 'A `@prop` docblock must be written as `{# @prop <name> <type> <Description.> #}`.');

                continue;
            }

            [$name, $type, $description] = [$prop['name'], $prop['type'], $prop['description']];
            $documentedNames[] = $name;

            try {
                new Prop($name, $type, $description);
            } catch (\InvalidArgumentException $e) {
                yield $this->issue($recipe, $file, 'component.prop.invalid', $e->getMessage());
            }

            if (!$this->typeValidator->isValid($type)) {
                yield $this->issue($recipe, $file, 'component.prop.invalid', \sprintf('Prop "%s" has an invalid type "%s": it must be a valid PHPDoc/PHPStan type with no spaces.', $name, $type));
            }

            yield from $this->checkDescription($recipe, $file, 'component.prop.invalid', \sprintf('Prop "%s"', $name), $description);

            if (null !== $declaration && null === $declaration->get($name)) {
                yield $this->issue($recipe, $file, 'component.prop.mismatch', \sprintf('Prop "%s" is documented with `@prop` but is not declared in the props tag.', $name));
            }
        }

        if (null !== $declaration) {
            foreach ($declaration->names() as $declaredName) {
                if (!\in_array($declaredName, $documentedNames, true)) {
                    yield $this->issue($recipe, $file, 'component.prop.mismatch', \sprintf('Prop "%s" is declared in the props tag but has no `{# @prop %1$s ... #}` docblock.', $declaredName));
                }
            }
        }
    }

    /**
     * @param list<array{body: string}> $matches
     * @param list<string>              $usedBlocks
     *
     * @return iterable<LintIssue>
     */
    private function checkBlocks(string $recipe, string $file, array $matches, array $usedBlocks): iterable
    {
        $documentedNames = [];

        foreach ($matches as $match) {
            $block = $this->parseBlockBody($match['body']);
            if (null === $block) {
                yield $this->issue($recipe, $file, 'component.block.invalid', 'A `@block` docblock must be written as `{# @block <name> <Description.> #}`.');

                continue;
            }

            [$name, $description] = [$block['name'], $block['description']];
            $documentedNames[] = $name;

            try {
                new Block($name, $description);
            } catch (\InvalidArgumentException $e) {
                yield $this->issue($recipe, $file, 'component.block.invalid', $e->getMessage());
            }

            yield from $this->checkDescription($recipe, $file, 'component.block.invalid', \sprintf('Block "%s"', $name), $description);

            if (!\in_array($name, $usedBlocks, true)) {
                yield $this->issue($recipe, $file, 'component.block.mismatch', \sprintf('Block "%s" is documented with `@block` but is never rendered in the template.', $name));
            }
        }

        foreach ($usedBlocks as $usedName) {
            if (!\in_array($usedName, $documentedNames, true)) {
                yield $this->issue($recipe, $file, 'component.block.mismatch', \sprintf('Block "%s" is rendered in the template but has no `{# @block %1$s ... #}` docblock.', $usedName));
            }
        }
    }

    /**
     * @return array{name: string, type: string, description: string}|null
     */
    private function parsePropBody(string $body): ?array
    {
        $parts = $this->tokenizeBody($body);
        if (\count($parts) < 3) {
            return null;
        }

        $name = array_shift($parts);
        $type = array_shift($parts);

        return ['name' => $name, 'type' => $type, 'description' => implode(' ', $parts)];
    }

    /**
     * @return array{name: string, description: string}|null
     */
    private function parseBlockBody(string $body): ?array
    {
        $parts = $this->tokenizeBody($body);
        if (\count($parts) < 2) {
            return null;
        }

        $name = array_shift($parts);

        return ['name' => $name, 'description' => implode(' ', $parts)];
    }

    /**
     * @return list<string>
     */
    private function tokenizeBody(string $body): array
    {
        $body = trim(preg_replace('/\s+/', ' ', $body));

        return '' === $body ? [] : explode(' ', $body);
    }

    /**
     * @return list<string>
     */
    private function extractUsedBlocks(string $contents): array
    {
        $names = [];
        foreach (self::RE_USED_BLOCKS as $pattern) {
            if (preg_match_all($pattern, $contents, $matches)) {
                $names = array_merge($names, $matches['name']);
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @return iterable<LintIssue>
     */
    private function checkDescription(string $recipe, string $file, string $category, string $subject, string $description): iterable
    {
        if ('' === $description) {
            yield $this->issue($recipe, $file, $category, \sprintf('%s is missing a description.', $subject));

            return;
        }

        if (!preg_match('/^\p{Lu}/u', $description)) {
            yield $this->issue($recipe, $file, $category, \sprintf('%s description must start with a capital letter.', $subject));
        }

        if (!str_ends_with($description, '.')) {
            yield $this->issue($recipe, $file, $category, \sprintf('%s description must end with a period.', $subject));
        }
    }

    private function issue(string $recipe, string $file, string $category, string $message): LintIssue
    {
        return new LintIssue(LintSeverity::Warning, $category, $message, $recipe, $file);
    }
}
