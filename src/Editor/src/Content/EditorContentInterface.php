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

interface EditorContentInterface
{
    public function getFormat(): EditorContentFormat;

    public function getRaw(): string|array;

    public function getMetadata(): array;

    public function isEmpty(): bool;
}
