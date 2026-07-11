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
use Doctrine\DBAL\Types\TextType;
use Symfony\UX\Editor\Content\HtmlContent;

final class EditorContentHtmlType extends TextType
{
    public const NAME = 'editor_html';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!$value instanceof HtmlContent) {
            throw new \InvalidArgumentException('Expected HtmlContent, got '.get_debug_type($value));
        }

        return $value->html;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?HtmlContent
    {
        return null === $value ? null : new HtmlContent((string) $value);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
