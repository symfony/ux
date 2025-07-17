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

use Twig\Error\Error;
use Twig\Template;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
class DeterministicTwigIdCalculator
{
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
     * Any differences are marked below.
     *
     * @return array{name: string, line: int}
     */
    private function guessTemplateInfo(): array
    {
        $template = null;
        $templateClass = null;

        $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT);
        foreach ($backtrace as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof Template) {
                $currentClass = \get_class($trace['object']);
                $isEmbedContainer = null === $templateClass ? false : str_starts_with($templateClass, $currentClass);
                // START CHANGE
                // if statement not needed
                // if (null === $this->name || ($this->name == $trace['object']->getTemplateName() && !$isEmbedContainer)) {
                $template = $trace['object'];
                // $templateClass = \get_class($trace['object']);
                // }
                // END CHANGE

                // START CHANGE
                // we want to find the FIRST matching template, not the original
                // END CHANGE
                break;
            }
        }

        // update template name
        // START CHANGE
        // don't check name property
        // if (null !== $template && null === $this->name) {
        if (null !== $template) {
            // END CHANGE
            // START CHANGE
            // set local variable
            $name = $template->getTemplateName();
            // END CHANGE
        }

        // update template path if any
        // START CHANGE
        // if statement not needed
        /*
        if (null !== $template && null === $this->sourcePath) {
            $src = $template->getSourceContext();
            $this->sourceCode = $src->getCode();
            $this->sourcePath = $src->getPath();
        }
        */

        // START CHANGE
        // if (null === $template || $this->lineno > -1) {
        // remove lineno property check
        if (null === $template) {
            // END CHANGE
            // START CHANGE
            // throw exception instead
            throw new \LogicException('Could not determine template while generating deterministic id.');
            // return;
            //
        }

        $r = new \ReflectionObject($template);
        $file = $r->getFileName();

        // START CHANGE
        // $exceptions = [$e = $this];
        $exceptions = [new Error('')];
        // not other exceptions to check
        /*
        while ($e = $e->getPrevious()) {
            $exceptions[] = $e;
        }
        */
        // END CHANGE

        while ($e = array_pop($exceptions)) {
            $traces = $e->getTrace();
            array_unshift($traces, ['file' => $e->getFile(), 'line' => $e->getLine()]);

            while ($trace = array_shift($traces)) {
                if (!isset($trace['file']) || !isset($trace['line']) || $file != $trace['file']) {
                    continue;
                }

                foreach ($template->getDebugInfo() as $codeLine => $templateLine) {
                    if ($codeLine <= $trace['line']) {
                        // update template line
                        // START CHANGE
                        // set local variable
                        $lineno = $templateLine;

                        // return the values
                        return ['name' => $name, 'line' => $lineno];
                        // return;
                        // END CHANGE
                    }
                }
            }
        }

        throw new \LogicException(\sprintf('Could not find line number in template "%s" while generating deterministic id.', $name));
    }
}
