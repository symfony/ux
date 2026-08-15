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
     * debug_backtrace() materializes every frame of the call stack, which is deep
     * during a request. The frames looked at below sit near the top, so the stack
     * is read through a growing window instead of all at once. The last entry is
     * unbounded, so the outcome is always the same as scanning the whole stack.
     */
    private const BACKTRACE_LIMITS = [16, 64, 0];

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
        [$callingEmbeddedTemplateIndex, $hostEmbeddedTemplateIndex] = $this->findCallerIndexes();

        return $this->stack[$name][$callingEmbeddedTemplateIndex][$hostEmbeddedTemplateIndex] ?? self::OUTER_BLOCK_FALLBACK_NAME;
    }

    private function findHostEmbeddedTemplateIndex(): int
    {
        foreach (self::BACKTRACE_LIMITS as $limit) {
            $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit);

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

            if (self::isWholeStack($backtrace, $limit)) {
                break;
            }
        }

        return 0;
    }

    /**
     * Both indexes come from the same frames, so the stack is read once.
     *
     * @return array{int, int} the calling embedded template index, then the host one
     */
    private function findCallerIndexes(): array
    {
        $calling = null;

        foreach (self::BACKTRACE_LIMITS as $limit) {
            $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit);

            $blockCallerStack = [];
            $renderer = null;

            foreach ($backtrace as $trace) {
                if (isset($trace['object']) && $trace['object'] instanceof Template) {
                    $classname = $trace['object']::class;
                    $templateIndex = self::getTemplateIndexFromTemplateClassname($classname);
                    // The first template frame is the one calling the block.
                    $calling ??= $templateIndex;
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
                    return [$calling, $templateIndex];
                }
            }

            if (self::isWholeStack($backtrace, $limit)) {
                break;
            }
        }

        // If the component is not an embedded one, just return 0, so the fallback content (aka nothing) is used.
        return [$calling ?? 0, 0];
    }

    /**
     * @param list<array<string, mixed>> $backtrace
     */
    private static function isWholeStack(array $backtrace, int $limit): bool
    {
        return 0 === $limit || \count($backtrace) < $limit;
    }

    private static function getTemplateIndexFromTemplateClassname(string $classname): int
    {
        return self::$templateIndexStack[$classname] ??= (int) substr($classname, strrpos($classname, '___') + 3);
    }
}
