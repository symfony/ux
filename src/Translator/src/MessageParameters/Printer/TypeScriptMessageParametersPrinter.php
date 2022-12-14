<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\MessageParameters\Printer;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @experimental
 */
final class TypeScriptMessageParametersPrinter
{
    /**
     * @param array<string, array{ type: 'number' }|array{ type: 'string', values?: list<string>}|array{ type: 'date' }> $parameters
     */
    public function print(array $parameters): string
    {
        if ([] === $parameters) {
            return 'NoParametersType';
        }

        $type = '{ ';
        foreach ($parameters as $parameterName => $parameter) {
            switch ($parameter['type']) {
                case 'number':
                    $value = 'number';
                    break;
                case 'string':
                    if (\is_array($parameter['values'] ?? null)) {
                        $value = implode(
                            '|',
                            array_map(
                                fn (string $val) => 'other' === $val ? 'string' : "'".$val."'",
                                $parameter['values']
                            )
                        );
                    } else {
                        $value = 'string';
                    }
                    break;
                case 'date':
                    $value = 'Date';
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Unknown type "%s" for parameter "%s"', $parameter['type'], $parameterName));
            }

            $type .= sprintf("'%s': %s, ", $parameterName, $value);
        }

        $type = rtrim($type, ', ');
        $type .= ' }';

        return $type;
    }
}
