<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use Symfony\UX\Editor\Content\PageContent;
use Symfony\UX\Editor\Exception\ContentSchemaException;

final class EditorContentPageType extends JsonType
{
    public const NAME = 'editor_page';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!$value instanceof PageContent) {
            throw new \InvalidArgumentException('Expected PageContent, got '.get_debug_type($value));
        }
        try {
            return json_encode([
                'html' => $value->html,
                'css' => $value->css,
                'assets' => $value->assets,
                'components' => $value->components,
                'metadata' => $value->getMetadata(),
            ], \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ContentSchemaException('Could not encode PageContent', 0, $e);
        }
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?PageContent
    {
        if (null === $value) {
            return null;
        }
        try {
            $arr = json_decode((string) $value, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ContentSchemaException('Malformed PageContent JSON', 0, $e);
        }

        return new PageContent(
            html: (string) ($arr['html'] ?? ''),
            css: (string) ($arr['css'] ?? ''),
            assets: $arr['assets'] ?? [],
            components: $arr['components'] ?? [],
            metadata: $arr['metadata'] ?? [],
        );
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
