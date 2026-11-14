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

use Symfony\UX\Translator\Intl\IntlMessageParser;
use Symfony\UX\Translator\Intl\Type;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class IntlMessageParametersExtractor implements ExtractorInterface
{
    /**
     * @var array<string, array<string, array{type: string, values?: list<string>}>>
     */
    private array $parametersByMessage = [];

    public function extract(string $message): array
    {
        // Early return if there is no parameter-like pattern in the message
        if (!str_contains($message, '{')) {
            return [];
        }

        if (isset($this->parametersByMessage[$message])) {
            return $this->parametersByMessage[$message];
        }

        $parameters = [];

        $intlMessageParser = new IntlMessageParser($message);
        $ast = $intlMessageParser->parse();
        if ($ast['err']) {
            throw new \InvalidArgumentException(\sprintf('The message "%s" is not a valid Intl message.', $message));
        }

        $nodes = $ast['val'];

        while ([] !== $nodes) {
            $node = array_shift($nodes);

            switch ($node['type']) {
                case Type::LITERAL:
                    break;

                case Type::ARGUMENT:
                    $parameters[$node['value']] = ['type' => 'string'];
                    break;

                case Type::NUMBER:
                    $parameters[$node['value']] = ['type' => 'number'];
                    break;

                case Type::DATE:
                case Type::TIME:
                    $parameters[$node['value']] = ['type' => 'date'];
                    break;

                case Type::SELECT:
                    $parameters[$node['value']] = [
                        'type' => 'string',
                        'values' => array_keys($node['options']),
                    ];

                    foreach ($node['options'] as $option) {
                        foreach ($option['value'] as $nodeValue) {
                            $nodes[] = $nodeValue;
                        }
                    }

                    break;

                case Type::PLURAL:
                    $parameters[$node['value']] = [
                        'type' => 'number',
                    ];

                    foreach ($node['options'] as $option) {
                        foreach ($option['value'] as $nodeValue) {
                            $nodes[] = $nodeValue;
                        }
                    }

                    break;
            }
        }

        return $this->parametersByMessage[$message] = $parameters;
    }
}
