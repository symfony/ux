<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\JsonType;
use Symfony\UX\Image\ImageAsset;

/**
 * Custom Doctrine DBAL type that stores an {@see ImageAsset} value object as a JSON column.
 *
 * DBAL types are not DI services; they are registered globally on the type registry. When
 * DoctrineBundle is installed and "ux_image.doctrine_type" is enabled,
 * {@see \Symfony\UX\Image\UXImageBundle::prependExtension()} registers this type under the
 * name {@see self::NAME} ("image_asset"). Without DoctrineBundle, this class is never
 * autoloaded and the bundle does not depend on doctrine/dbal. To register it by hand instead:
 *
 *     # config/packages/doctrine.yaml
 *     doctrine:
 *         dbal:
 *             types:
 *                 image_asset: Symfony\UX\Image\Doctrine\ImageAssetType
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ImageAssetType extends JsonType
{
    public const NAME = 'image_asset';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?ImageAsset
    {
        if (null === $value || '' === $value) {
            return null;
        }

        /** @var array<string, mixed>|scalar|null $data */
        $data = parent::convertToPHPValue($value, $platform);

        if (!\is_array($data)) {
            throw ValueNotConvertible::new($value, self::NAME);
        }

        try {
            return ImageAsset::fromArray($data);
        } catch (\Throwable $e) {
            throw ValueNotConvertible::new($value, self::NAME, $e->getMessage(), $e);
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof ImageAsset) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('Expected ImageAsset instance, got '.get_debug_type($value));
        }

        // The ImageAsset object is readonly, so we can safely convert to array
        return parent::convertToDatabaseValue($value->toArray(), $platform);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
