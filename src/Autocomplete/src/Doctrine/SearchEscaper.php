<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Doctrine;

use Doctrine\ORM\Query\Lexer;

/**
 * Adapted from EasyCorp/EasyAdminBundle Escaper.
 */
class SearchEscaper
{
    public const DQL_ALIAS_PREFIX = 'autocomplete_';

    /**
     * Some words (e.g. "order") are reserved keywords in the DQL (Doctrine Query Language).
     * That's why when using entity names as DQL aliases, we need to escape
     * those reserved keywords.
     *
     * This method ensures that the given entity name can be used as a DQL alias.
     * Most of them are left unchanged (e.g. "category" or "invoice") but others
     * will include a prefix to escape them (e.g. "order" becomes "autocomplete_order").
     */
    public static function escapeDqlAlias(string $entityName): string
    {
        if (self::isDqlReservedKeyword($entityName)) {
            return self::DQL_ALIAS_PREFIX.$entityName;
        }

        return $entityName;
    }

    /**
     * Determines if a string is a reserved keyword in DQL (Doctrine Query Language).
     */
    private static function isDqlReservedKeyword(string $string): bool
    {
        $lexer = new Lexer($string);

        $lexer->moveNext();
        $token = $lexer->lookahead;

        // backwards compat for when $token changed from array to object
        // https://github.com/doctrine/lexer/pull/79
        $type = \is_array($token) ? $token['type'] : $token->type;

        if (200 <= $type) {
            return true;
        }

        return false;
    }
}
