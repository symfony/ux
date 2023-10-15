<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\MessageParameters;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Translator\MessageParameters\Extractor\IntlMessageParametersExtractor;

class IntlMessageParametersExtractorTest extends TestCase
{
    /**
     * @dataProvider provideExtract
     */
    public function testExtract(string $message, array $expectedParameters)
    {
        $intlMessageParametersExtractor = new IntlMessageParametersExtractor();

        static::assertEquals($expectedParameters, $intlMessageParametersExtractor->extract($message));
    }

    public function provideExtract()
    {
        yield [
            'Symfony is great!',
            [],
        ];

        yield [
            'Symfony is {what}!',
            ['what' => ['type' => 'string']],
        ];

        yield [
            '{framework} is {what}!',
            [
                'framework' => ['type' => 'string'],
                'what' => ['type' => 'string'],
            ],
        ];

        yield [
            'I have {numCats, number} cats.',
            ['numCats' => ['type' => 'number']],
        ];

        yield [
            'Almost {pctBlack, number, ::percent} of my cats are black.',
            ['pctBlack' => ['type' => 'number']],
        ];

        yield [
            'The price of this bagel is {num, number, ::sign-always compact-short currency/GBP}',
            ['num' => ['type' => 'number']],
        ];

        yield [
            'Coupon expires at {expires, time, short}',
            ['expires' => ['type' => 'date']],
        ];

        yield [
            <<<TXT
{gender, select,
    male {He}
    female {She}
    other {They}
} will respond shortly.
TXT,
            [
                'gender' => ['type' => 'string', 'values' => ['male', 'female', 'other']],
            ],
        ];

        yield [
            <<<TXT
{taxableArea, select,
    yes {An additional {taxRate, number, percent} tax will be collected.}
    other {No taxes apply.}
}
TXT,
            [
                'taxableArea' => ['type' => 'string', 'values' => ['yes', 'other']],
                'taxRate' => ['type' => 'number'],
            ],
        ];

        yield [
            <<<TXT
{gender_of_host, select,
    female {{num_guests, plural, offset:1
        =0    {{host} does not give a party.}
        =1    {{host} invites {guest} to her party.}
        =2    {{host} invites {guest} and one other person to her party.}
        other {{host} invites {guest} and # other people to her party.}
    }}
    male {{num_guests, plural, offset:1
        =0    {{host} does not give a party.}
        =1    {{host} invites {guest} to his party.}
        =2    {{host} invites {guest} and one other person to his party.}
        other {{host} invites {guest} and # other people to his party.}
    }}
    other {{num_guests, plural, offset:1
        =0    {{host} does not give a party.}
        =1    {{host} invites {guest} to their party.}
        =2    {{host} invites {guest} and one other person to their party.}
        other {{host} invites {guest} and # other people to their party.}
    }}
}
TXT,
            [
                'gender_of_host' => ['type' => 'string', 'values' => ['female', 'male', 'other']],
                'num_guests' => ['type' => 'number'],
                'host' => ['type' => 'string'],
                'guest' => ['type' => 'string'],
            ],
        ];
    }
}
