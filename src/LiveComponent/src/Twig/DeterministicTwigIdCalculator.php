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

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
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
     */
    public function calculateDeterministicId(): string
    {
        $error = new Error('');
        $error->guess();

        if (!$error->getSourceContext()) {
            throw new \LogicException('Could not determine which Twig template is rendering');
        }

        $fileAndLine = sprintf('%s-%d', $error->getSourceContext()?->getPath(), $error->getTemplateLine());
        if (!isset($this->lineAndFileCounts[$fileAndLine])) {
            $this->lineAndFileCounts[$fileAndLine] = 0;
        }

        $id = sprintf(
            'live-%s-%d',
            crc32($fileAndLine),
            $this->lineAndFileCounts[$fileAndLine]
        );
        ++$this->lineAndFileCounts[$fileAndLine];

        return $id;
    }
}
