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
use Symfony\UX\Editor\Content\BlockContent;
use Symfony\UX\Editor\Exception\ContentSchemaException;

final class EditorContentBlocksType extends JsonType
{
    public const NAME = 'editor_blocks';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!$value instanceof BlockContent) {
            throw new \InvalidArgumentException('Expected BlockContent, got '.get_debug_type($value));
        }
        try {
            return json_encode([
                'version' => $value->schemaVersion,
                'blocks' => $value->blocks,
                'metadata' => $value->getMetadata(),
            ], \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ContentSchemaException('Could not encode BlockContent', 0, $e);
        }
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?BlockContent
    {
        if (null === $value) {
            return null;
        }
        try {
            $arr = json_decode((string) $value, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ContentSchemaException('Malformed BlockContent JSON', 0, $e);
        }

        return new BlockContent(
            blocks: $arr['blocks'] ?? [],
            schemaVersion: (string) ($arr['version'] ?? '1.0'),
            metadata: $arr['metadata'] ?? [],
        );
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
