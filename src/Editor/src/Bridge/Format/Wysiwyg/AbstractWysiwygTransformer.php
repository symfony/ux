<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\Format\Wysiwyg;

use Symfony\UX\Editor\Content\EditorContentInterface;
use Symfony\UX\Editor\Content\HtmlContent;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;
use Symfony\UX\Editor\Form\DataTransformer\StorageShape;

abstract class AbstractWysiwygTransformer implements EditorContentTransformerInterface
{
    public function getContentClass(): string
    {
        return HtmlContent::class;
    }

    public function getStorageShape(): StorageShape
    {
        return StorageShape::Scalar;
    }

    public function transform(?EditorContentInterface $content): ?string
    {
        if (null === $content) {
            return null;
        }
        if (!$content instanceof HtmlContent) {
            throw new \InvalidArgumentException(\sprintf('Expected HtmlContent, got "%s"', get_debug_type($content)));
        }

        return $content->html;
    }

    public function reverseTransform(mixed $stored): ?HtmlContent
    {
        if (null === $stored || '' === $stored) {
            return null;
        }

        return new HtmlContent((string) $stored, ['bridgeId' => $this->getBridgeId()]);
    }

    abstract public function getBridgeId(): string;
}
