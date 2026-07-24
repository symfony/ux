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
use Symfony\UX\Toolkit\Component\StimulusControllerSource;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\Lint\KitCheckerInterface;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * Normalizes a Stimulus controller's `@value`/`@target`/`@action` docblock tags and keeps them
 * consistent with the code, mirroring {@see ComponentDocChecker} for Twig components:
 *  - the description itself (present, capitalized, ending with a period);
 *  - `@value`/`@target` tags against the `static values`/`static targets` declarations (both ways);
 *  - each `@action` against a method actually defined on the controller.
 *
 * Documentation is opt-in: a controller with no tags at all is left untouched, so existing
 * undocumented controllers are not reported. Once any tag is present, the whole surface is enforced.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class StimulusControllerDocChecker implements KitCheckerInterface
{
    private const RE_BARE_TAG = '/@(?P<tag>value|target|action)(?!\h+[A-Za-z_$])/';

    public function check(Kit $kit): iterable
    {
        foreach ($kit->getRecipes() as $recipe) {
            $dir = Path::join($recipe->absolutePath, 'assets/controllers');
            if (!is_dir($dir)) {
                continue;
            }

            $finder = new Finder()->in($dir)->files()->name('*_controller.js')->sortByName();
            foreach ($finder as $file) {
                yield from $this->checkFile($recipe->name, 'assets/controllers/'.$file->getRelativePathname(), $file->getContents());
            }
        }
    }

    /**
     * @return iterable<LintIssue>
     */
    private function checkFile(string $recipe, string $file, string $contents): iterable
    {
        $controller = new StimulusControllerSource($contents);
        $tags = $controller->tags();

        // Opt-in: nothing documented, nothing to enforce.
        if ([] === $tags['value'] && [] === $tags['target'] && [] === $tags['action']) {
            return;
        }

        if (preg_match(self::RE_BARE_TAG, $contents)) {
            yield $this->issue($recipe, $file, 'stimulus.doc.invalid', 'A `@value`/`@target`/`@action` tag must be written as `@<tag> <name> <Description.>`.');
        }

        yield from $this->checkGroup($recipe, $file, 'value', 'Value', $tags['value'], array_column($controller->values(), 'name'));
        yield from $this->checkGroup($recipe, $file, 'target', 'Target', $tags['target'], $controller->targets());

        foreach ($tags['action'] as $name => $description) {
            if (!$this->hasMethod($contents, $name)) {
                yield $this->issue($recipe, $file, 'stimulus.action.mismatch', \sprintf('Action "%s" is documented with `@action` but no matching method is defined on the controller.', $name));
            }

            yield from $this->checkDescription($recipe, $file, 'stimulus.action.invalid', \sprintf('Action "%s"', $name), $description);
        }
    }

    /**
     * @param array<string, string> $documented documented name => description
     * @param list<string>          $declared   names declared in the code
     *
     * @return iterable<LintIssue>
     */
    private function checkGroup(string $recipe, string $file, string $tag, string $label, array $documented, array $declared): iterable
    {
        foreach ($documented as $name => $description) {
            if (!\in_array($name, $declared, true)) {
                yield $this->issue($recipe, $file, \sprintf('stimulus.%s.mismatch', $tag), \sprintf('%s "%s" is documented with `@%s` but is not declared in the controller.', $label, $name, $tag));
            }

            yield from $this->checkDescription($recipe, $file, \sprintf('stimulus.%s.invalid', $tag), \sprintf('%s "%s"', $label, $name), $description);
        }

        foreach ($declared as $name) {
            if (!\array_key_exists($name, $documented)) {
                yield $this->issue($recipe, $file, \sprintf('stimulus.%s.mismatch', $tag), \sprintf('%s "%s" is declared in the controller but has no `@%s %2$s ...` tag.', $label, $name, $tag));
            }
        }
    }

    /**
     * A method definition (`name(...) {`) or a function-valued class field (`name = () =>`,
     * `name = function`). Bare call-sites like `name()` are deliberately not matched, so an
     * `@action` whose method is only *called* (never defined) is still reported.
     */
    private function hasMethod(string $contents, string $name): bool
    {
        $name = preg_quote($name, '/');

        return 1 === preg_match('/(?<![\w.$#])'.$name.'\s*(?:\([^)]*\)\s*\{|=\s*(?:async\s+)?(?:function\b|\([^)]*\)\s*=>))/', $contents);
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
