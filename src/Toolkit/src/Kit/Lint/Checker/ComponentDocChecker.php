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
use Symfony\UX\Toolkit\Component\ComponentDocScanner;
use Symfony\UX\Toolkit\Component\PhpStanTypeValidator;
use Symfony\UX\Toolkit\Component\Prop;
use Symfony\UX\Toolkit\Component\PropsDeclaration;
use Symfony\UX\Toolkit\Component\PropsDeclarationParser;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * Normalizes the prop and block documentation of every component template:
 *  - each prop is documented by a `## <type> <description>` comment above its name in the
 *    `{% props %}` tag (valid PHPDoc type for props, capitalized description ending with a period);
 *  - each rendered block (`{% block %}`, `block(outerBlocks.x)`, `block('x')`) is documented by a
 *    `{## <description> #}` doc comment placed directly above it.
 *
 * Consistency is enforced both ways: every declared prop must be documented and vice versa, and
 * every rendered block must be documented. Default values are never documented — they live only in
 * the `{% props %}` declaration, the single source of truth.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ComponentDocChecker implements KitCheckerInterface
{
    private readonly PropsDeclarationParser $propsDeclarationParser;
    private readonly ComponentDocScanner $scanner;
    private readonly PhpStanTypeValidator $typeValidator;

    public function __construct()
    {
        $this->propsDeclarationParser = new PropsDeclarationParser();
        $this->scanner = new ComponentDocScanner();
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
        $declaration = $this->propsDeclarationParser->parse($contents);
        $blocks = $this->scanner->scanBlocks($contents);

        // Nothing declared or rendered: nothing to check (e.g. a static partial).
        if (null === $declaration && [] === $blocks['used']) {
            return;
        }

        yield from $this->checkProps($recipe, $file, $declaration);
        yield from $this->checkBlocks($recipe, $file, $blocks['docs'], $blocks['used']);
    }

    /**
     * @return iterable<LintIssue>
     */
    private function checkProps(string $recipe, string $file, ?PropsDeclaration $declaration): iterable
    {
        if (null === $declaration) {
            return;
        }

        foreach ($declaration->props as $prop) {
            if (null === $prop->documentation) {
                yield $this->issue($recipe, $file, 'component.prop.mismatch', \sprintf('Prop "%s" is declared in the props tag but has no `## <type> <description>` doc comment above it.', $prop->name));

                continue;
            }

            [$type, $description] = ComponentDocScanner::splitTypeAndDescription($prop->documentation);

            try {
                new Prop($prop->name, $type, $description);
            } catch (\InvalidArgumentException $e) {
                yield $this->issue($recipe, $file, 'component.prop.invalid', $e->getMessage());
            }

            if (!$this->typeValidator->isValid($type)) {
                yield $this->issue($recipe, $file, 'component.prop.invalid', \sprintf('Prop "%s" has an invalid type "%s": it must be a valid PHPDoc/PHPStan type with no spaces.', $prop->name, $type));
            }

            yield from $this->checkDescription($recipe, $file, 'component.prop.invalid', \sprintf('Prop "%s"', $prop->name), $description);
        }
    }

    /**
     * @param list<array{name: string, description: string}> $blockDocs
     * @param list<string>                                   $usedBlocks
     *
     * @return iterable<LintIssue>
     */
    private function checkBlocks(string $recipe, string $file, array $blockDocs, array $usedBlocks): iterable
    {
        $documentedNames = [];

        foreach ($blockDocs as $block) {
            $name = $block['name'];
            if (\in_array($name, $documentedNames, true)) {
                continue;
            }
            $documentedNames[] = $name;

            try {
                new Block($name, $block['description']);
            } catch (\InvalidArgumentException $e) {
                yield $this->issue($recipe, $file, 'component.block.invalid', $e->getMessage());
            }

            yield from $this->checkDescription($recipe, $file, 'component.block.invalid', \sprintf('Block "%s"', $name), $block['description']);
        }

        foreach ($usedBlocks as $usedName) {
            if (!\in_array($usedName, $documentedNames, true)) {
                yield $this->issue($recipe, $file, 'component.block.mismatch', \sprintf('Block "%s" is rendered in the template but has no `{## ... #}` doc comment above it.', $usedName));
            }
        }
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
