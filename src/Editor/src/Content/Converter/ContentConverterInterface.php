<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Content\Converter;

use Symfony\UX\Editor\Content\EditorContentInterface;

interface ContentConverterInterface
{
    public function getFrom(): string;

    public function getTo(): string;

    public function convert(EditorContentInterface $content): EditorContentInterface;
}
