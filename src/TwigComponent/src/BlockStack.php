<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

use Twig\Template;

/**
 * @author Bart Vanderstukken <bart.vanderstukken@gmail.com>
 *
 * @internal
 */
final class BlockStack
{
    private const OUTER_BLOCK_PREFIX = 'outer__';
    public const OUTER_BLOCK_FALLBACK_NAME = self::OUTER_BLOCK_PREFIX.'block_fallback';

    /**
     * @var array<string, array<int, array<int, string>>>
     */
    private array $stack;

    /**
     * @var array<class-string, int>
     */
    private static array $templateIndexStack = [];

    public function convert(array $blocks, int $targetEmbeddedTemplateIndex): array
    {
        $newBlocks = [];
        $hostEmbeddedTemplateIndex = null;
        foreach ($blocks as $blockName => $block) {
            // Keep already converted outer blocks untouched
            if (str_starts_with($blockName, self::OUTER_BLOCK_PREFIX)) {
                $newBlocks[$blockName] = $block;
                continue;
            }

            // Determine the location of the block where it is defined in the host Template.
            // Each component has its own embedded template. That template's index uniquely
            // identifies the block definition.
            $hostEmbeddedTemplateIndex ??= $this->findHostEmbeddedTemplateIndex();

            // Change the name of outer blocks to something unique so blocks of nested components aren't overridden,
            // which otherwise might cause a recursion loop when nesting components.
            $newName = self::OUTER_BLOCK_PREFIX.$blockName.'_'.mt_rand();
            $newBlocks[$newName] = $block;

            // The host index combined with the index of the embedded template where the block can be used (target)
            // allows us to remember the link between the original name and the new randomized name.
            // That way we can map a call like `block(outerBlocks.block_name)` to the randomized name.
            $this->stack[$blockName][$targetEmbeddedTemplateIndex][$hostEmbeddedTemplateIndex] = $newName;
        }

        return $newBlocks;
    }

    public function __call(string $name, array $arguments)
    {
        $callingEmbeddedTemplateIndex = $this->findCallingEmbeddedTemplateIndex();
        $hostEmbeddedTemplateIndex = $this->findHostEmbeddedTemplateIndexFromCaller();

        return $this->stack[$name][$callingEmbeddedTemplateIndex][$hostEmbeddedTemplateIndex] ?? self::OUTER_BLOCK_FALLBACK_NAME;
    }

    private function findHostEmbeddedTemplateIndex(): int
    {
        $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT);

        foreach ($backtrace as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof Template) {
                $classname = $trace['object']::class;
                $templateIndex = self::getTemplateIndexFromTemplateClassname($classname);
                if ($templateIndex) {
                    // If there's no template index, then we're in a component template
                    // and we need to go up until we find the embedded template
                    // (which will have the block definitions).
                    return $templateIndex;
                }
            }
        }

        return 0;
    }

    private function findCallingEmbeddedTemplateIndex(): int
    {
        $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT);

        foreach ($backtrace as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof Template) {
                return self::getTemplateIndexFromTemplateClassname($trace['object']::class);
            }
        }
    }

    private function findHostEmbeddedTemplateIndexFromCaller(): int
    {
        $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT);

        $blockCallerStack = [];
        $renderer = null;

        foreach ($backtrace as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof Template) {
                $classname = $trace['object']::class;
                $templateIndex = self::getTemplateIndexFromTemplateClassname($classname);
                if (null === $renderer) {
                    if ($templateIndex) {
                        // This class is an embedded template.
                        // Next class is either the renderer or a previous template that's passing blocks through.
                        $blockCallerStack[$classname] = $classname;
                        continue;
                    }
                    // If it's not an embedded template anymore, we've reached the renderer.
                    // From now on we'll travel back up the hierarchy.
                    $renderer = $classname;
                    continue;
                }
                if ($classname === $renderer || isset($blockCallerStack[$classname])) {
                    continue;
                }

                if (!$templateIndex) {
                    continue;
                }

                // This is the first template that's not part of the callstack,
                // so it's the template that has the outer block definition.
                return $templateIndex;
            }
        }

        // If the component is not an embedded one, just return 0, so the fallback content (aka nothing) is used.
        return 0;
    }

    private static function getTemplateIndexFromTemplateClassname(string $classname): int
    {
        return self::$templateIndexStack[$classname] ??= (int) substr($classname, strrpos($classname, '___') + 3);
    }
}
