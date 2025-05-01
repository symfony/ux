<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Asset;

use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\File\File;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
class StimulusController
{
    /**
     * @param non-empty-string $name
     * @param list<File>       $files
     */
    public function __construct(
        public readonly string $name,
        public readonly array $files,
    ) {
        Assert::stimulusControllerName($this->name);

        if ([] === $files) {
            throw new \InvalidArgumentException(\sprintf('Stimulus controller "%s" has no files.', $name));
        }
    }
}
