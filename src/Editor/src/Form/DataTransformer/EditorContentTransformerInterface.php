<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Form\DataTransformer;

use Symfony\UX\Editor\Content\EditorContentInterface;

interface EditorContentTransformerInterface
{
    public function getBridgeId(): string;

    public function getContentClass(): string;

    public function getStorageShape(): StorageShape;

    public function transform(?EditorContentInterface $content): mixed;

    public function reverseTransform(mixed $stored): ?EditorContentInterface;
}
