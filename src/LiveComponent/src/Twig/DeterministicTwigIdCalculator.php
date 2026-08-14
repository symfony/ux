<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Twig;

use Twig\Template;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
class DeterministicTwigIdCalculator
{
    /**
     * Windows used to read the call stack, growing until the rendering template is
     * found. The last one is unbounded, so the outcome always matches scanning the
     * whole stack. Same sequence as Symfony\UX\TwigComponent\BlockStack.
     */
    private const BACKTRACE_LIMITS = [16, 64, 0];

    private array $lineAndFileCounts = [];

    /**
     * Attempts to return a unique + deterministic id/hash for the given Twig line.
     *
     * This method is meant to be called *while* a Twig template is rendering.
     * It will return a string based on the filename & line number of the Twig
     * template that is currently rendering. That string will be unique (e.g. if
     * you call this method multiple times on the same line, you will get a different
     * value each time), but deterministic: if you call this method on a future
     * request on the same file+line, you will get the same string back. Or, if you
     * called this method 3 times on the same line during one request, you will
     * get the same value back if you call it 3 times on a future request for
     * that same file & line.
     *
     * @param bool        $increment Whether to increment the counter for this file+line
     * @param string|null $key       An optional key to use instead of the incremented counter
     */
    public function calculateDeterministicId(bool $increment = true, ?string $key = null): string
    {
        $lineData = $this->guessTemplateInfo();

        $fileAndLine = \sprintf('%s-%d', $lineData['name'], $lineData['line']);
        if (!isset($this->lineAndFileCounts[$fileAndLine])) {
            $this->lineAndFileCounts[$fileAndLine] = 0;
        }

        $id = \sprintf(
            'live-%s-%s',
            crc32($fileAndLine),
            null !== $key ? $key : $this->lineAndFileCounts[$fileAndLine]
        );

        if ($increment) {
            ++$this->lineAndFileCounts[$fileAndLine];
        }

        return $id;
    }

    public function reset(): void
    {
        $this->lineAndFileCounts = [];
    }

    /**
     * Adapted from Twig\Error\Error::guessTemplateInfo().
     *
     * Both the rendering template and the line it is rendering are read from the
     * call stack. Materializing that stack is the whole cost of this method, and
     * during a request it is deep while the frames of interest sit near the top,
     * so it is walked through a growing window instead of all at once.
     *
     * @return array{name: string, line: int}
     */
    private function guessTemplateInfo(): array
    {
        foreach (self::BACKTRACE_LIMITS as $limit) {
            $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit);
            $isWholeStack = 0 === $limit || \count($backtrace) < $limit;

            // we want to find the FIRST matching template, not the original
            $template = null;
            foreach ($backtrace as $trace) {
                if (isset($trace['object']) && $trace['object'] instanceof Template) {
                    $template = $trace['object'];
                    break;
                }
            }

            if (null === $template) {
                if ($isWholeStack) {
                    break;
                }

                continue;
            }

            $name = $template->getTemplateName();
            $file = new \ReflectionObject($template)->getFileName();

            foreach ($backtrace as $trace) {
                if (!isset($trace['file']) || !isset($trace['line']) || $file != $trace['file']) {
                    continue;
                }

                foreach ($template->getDebugInfo() as $codeLine => $templateLine) {
                    if ($codeLine <= $trace['line']) {
                        return ['name' => $name, 'line' => $templateLine];
                    }
                }
            }

            if ($isWholeStack) {
                throw new \LogicException(\sprintf('Could not find line number in template "%s" while generating deterministic id.', $name));
            }
        }

        throw new \LogicException('Could not determine template while generating deterministic id.');
    }
}
