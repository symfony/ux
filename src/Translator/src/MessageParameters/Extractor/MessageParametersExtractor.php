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
final class MessageParametersExtractor implements ExtractorInterface
{
    private const RE_PARAMETER = '/(%\w+%)|({{ \w+ }})/';

    public function extract(string $message): array
    {
        $parameters = [];

        if (false !== preg_match_all(self::RE_PARAMETER, $message, $matches)) {
            foreach ($matches[0] as $match) {
                $parameters[$match] = [
                    'type' => '%count%' === $match ? 'number' : 'string',
                ];
            }
        }

        return $parameters;
    }
}
