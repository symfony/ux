<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\MessageParameters\Extractor;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @experimental
 */
interface ExtractorInterface
{
    /**
     * @return array<string, array{ type: 'number' }|array{ type: 'string', values?: list<string>}|array{ type: 'date' }>
     *
     * @throws \Exception
     */
    public function extract(string $message): array;
}
