<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Content;

abstract class EditorContent implements EditorContentInterface
{
    public function __construct(
        public readonly EditorContentFormat $format,
        public readonly array $metadata = [],
    ) {
    }

    public function getFormat(): EditorContentFormat
    {
        return $this->format;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
