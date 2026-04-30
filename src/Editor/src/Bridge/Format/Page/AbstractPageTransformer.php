<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\Format\Page;

use Symfony\UX\Editor\Content\EditorContentInterface;
use Symfony\UX\Editor\Content\PageContent;
use Symfony\UX\Editor\Exception\ContentSchemaException;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;
use Symfony\UX\Editor\Form\DataTransformer\StorageShape;

abstract class AbstractPageTransformer implements EditorContentTransformerInterface
{
    public function getContentClass(): string
    {
        return PageContent::class;
    }

    public function getStorageShape(): StorageShape
    {
        return StorageShape::Json;
    }

    public function transform(?EditorContentInterface $content): ?array
    {
        if (null === $content) {
            return null;
        }
        if (!$content instanceof PageContent) {
            throw new \InvalidArgumentException(sprintf('Expected PageContent, got %s', get_debug_type($content)));
        }

        return [
            'html' => $content->html,
            'css' => $content->css,
            'assets' => $content->assets,
            'components' => $content->components,
        ];
    }

    public function reverseTransform(mixed $stored): ?PageContent
    {
        if (null === $stored || [] === $stored) {
            return null;
        }
        if (!\is_array($stored)) {
            throw new ContentSchemaException(sprintf('Expected array, got %s', get_debug_type($stored)));
        }
        $html = $stored['html'] ?? '';
        $css = $stored['css'] ?? '';
        if (!\is_string($html) || !\is_string($css)) {
            throw new ContentSchemaException('"html" and "css" must be strings');
        }

        return new PageContent(
            html: $html,
            css: $css,
            assets: \is_array($stored['assets'] ?? null) ? $stored['assets'] : [],
            components: \is_array($stored['components'] ?? null) ? $stored['components'] : [],
            metadata: ['bridgeId' => $this->getBridgeId()],
        );
    }

    abstract public function getBridgeId(): string;
}
