<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\Printer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Translator\MessageParameters\Printer\TypeScriptMessageParametersPrinter;

class TypeScriptMessageParametersPrinterTest extends TestCase
{
    /**
     * @dataProvider providePrint
     */
    public function testPrint(array $parameters, string $expectedTypeScriptType)
    {
        $typeScriptMessageParametersPrinter = new TypeScriptMessageParametersPrinter();

        static::assertSame($expectedTypeScriptType, $typeScriptMessageParametersPrinter->print($parameters));
    }

    public static function providePrint()
    {
        yield [
            [],
            'NoParametersType',
        ];

        yield [
            ['%what%' => ['type' => 'string']],
            "{ '%what%': string }",
        ];

        yield [
            ['what' => ['type' => 'string']],
            "{ 'what': string }",
        ];

        yield [
            [
                'framework' => ['type' => 'string'],
                'what' => ['type' => 'string'],
            ],
            "{ 'framework': string, 'what': string }",
        ];

        yield [
            [
                '%framework%' => ['type' => 'string'],
                '%what%' => ['type' => 'string'],
            ],
            "{ '%framework%': string, '%what%': string }",
        ];

        yield [
            [
                '%framework%' => ['type' => 'string'],
                '%years%' => ['type' => 'string'],
            ],
            "{ '%framework%': string, '%years%': string }",
        ];

        yield [
            ['%count%' => ['type' => 'number']],
            "{ '%count%': number }",
        ];

        yield [
            ['{{ limit }}' => ['type' => 'string']],
            "{ '{{ limit }}': string }",
        ];

        yield [
            ['numCats' => ['type' => 'number']],
            "{ 'numCats': number }",
        ];

        yield [
            ['num' => ['type' => 'number']],
            "{ 'num': number }",
        ];

        yield [
            ['expires' => ['type' => 'date']],
            "{ 'expires': Date }",
        ];

        yield [
            [
                'gender' => ['type' => 'string', 'values' => ['male', 'female', 'other']],
            ],
            "{ 'gender': 'male'|'female'|string }",
        ];

        yield [
            [
                'taxableArea' => ['type' => 'string', 'values' => ['yes', 'other']],
                'taxRate' => ['type' => 'number'],
            ],
            "{ 'taxableArea': 'yes'|string, 'taxRate': number }",
        ];

        yield [
            [
                'gender_of_host' => ['type' => 'string', 'values' => ['female', 'male', 'other']],
                'num_guests' => ['type' => 'number'],
                'host' => ['type' => 'string'],
                'guest' => ['type' => 'string'],
            ],
            "{ 'gender_of_host': 'female'|'male'|string, 'num_guests': number, 'host': string, 'guest': string }",
        ];
    }
}
