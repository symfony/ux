<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\Intl;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Translator\Intl\ErrorKind;
use Symfony\UX\Translator\Intl\IntlMessageParser;
use Symfony\UX\Translator\Intl\Location;
use Symfony\UX\Translator\Intl\Position;
use Symfony\UX\Translator\Intl\Type;

class IntlMessageParserTest extends TestCase
{
    /**
     * @dataProvider provideParse
     */
    public function testIntlMessageParser(string $message, array $expectedAst): void
    {
        $intlMessageParser = new IntlMessageParser($message);

        static::assertEquals($expectedAst, $intlMessageParser->parse());
    }

    public function provideParse()
    {
        yield 'no parameters' => [
            'Hello world!',
            [
                'val' => [
                    [
                        'type' => Type::LITERAL,
                        'value' => 'Hello world!',
                        'location' => new Location(new Position(0, 1, 1), new Position(12, 1, 13)),
                    ],
                ],
                'err' => null,
            ],
        ];

        yield 'with emoji' => [
            "We hope we've met your expectations 😊",
            [
                'val' => [
                    [
                        'type' => Type::LITERAL,
                        'value' => "We hope we've met your expectations 😊",
                        'location' => new Location(new Position(0, 1, 1), new Position(37, 1, 38)),
                    ],
                ],
                'err' => null,
            ],
        ];

        yield 'with HTML' => [
            'Hello <b>world</b>!',
            [
                'val' => [
                    [
                        'type' => Type::LITERAL,
                        'value' => 'Hello <b>world</b>!',
                        'location' => new Location(new Position(0, 1, 1), new Position(19, 1, 20)),
                    ],
                ],
                'err' => null,
            ],
        ];

        yield 'one parameter' => [
            'Hello {name}!',
            [
                'val' => [
                    [
                        'type' => Type::LITERAL,
                        'value' => 'Hello ',
                        'location' => new Location(new Position(0, 1, 1), new Position(6, 1, 7)),
                    ],
                    [
                        'type' => Type::ARGUMENT,
                        'value' => 'name',
                        'location' => new Location(new Position(6, 1, 7), new Position(12, 1, 13)),
                    ],
                    [
                        'type' => Type::LITERAL,
                        'value' => '!',
                        'location' => new Location(new Position(12, 1, 13), new Position(13, 1, 14)),
                    ],
                ],
                'err' => null,
            ],
        ];

        yield 'multiples parameters' => [
            'Hello {firstName}, welcome to {hotelName}',
            [
                'val' => [
                    [
                        'type' => Type::LITERAL,
                        'value' => 'Hello ',
                        'location' => new Location(new Position(0, 1, 1), new Position(6, 1, 7)),
                    ],
                    [
                        'type' => Type::ARGUMENT,
                        'value' => 'firstName',
                        'location' => new Location(new Position(6, 1, 7), new Position(17, 1, 18)),
                    ],
                    [
                        'type' => Type::LITERAL,
                        'value' => ', welcome to ',
                        'location' => new Location(new Position(17, 1, 18), new Position(30, 1, 31)),
                    ],
                    [
                        'type' => Type::ARGUMENT,
                        'value' => 'hotelName',
                        'location' => new Location(new Position(30, 1, 31), new Position(41, 1, 42)),
                    ],
                ],
                'err' => null,
            ],
        ];

        yield 'plural' => [
            <<<'EOT'
You have {itemCount, plural,
    =0 {no items}
    one {1 item}
    other {{itemCount} items}
}.
EOT,
            [
                'val' => [
                    [
                        'type' => Type::LITERAL,
                        'value' => 'You have ',
                        'location' => new Location(new Position(0, 1, 1), new Position(9, 1, 10)),
                    ],
                    [
                        'type' => Type::PLURAL,
                        'value' => 'itemCount',
                        'offset' => 0,
                        'options' => [
                            '=0' => [
                                'value' => [
                                    [
                                        'type' => Type::LITERAL,
                                        'value' => 'no items',
                                        'location' => new Location(new Position(37, 2, 9), new Position(45, 2, 17)),
                                    ],
                                ],
                                'location' => new Location(new Position(36, 2, 8), new Position(46, 2, 18)),
                            ],
                            'one' => [
                                'value' => [
                                    [
                                        'type' => Type::LITERAL,
                                        'value' => '1 item',
                                        'location' => new Location(new Position(56, 3, 10), new Position(62, 3, 16)),
                                    ],
                                ],
                                'location' => new Location(new Position(55, 3, 9), new Position(63, 3, 17)),
                            ],
                            'other' => [
                                'value' => [
                                    [
                                        'type' => Type::ARGUMENT,
                                        'value' => 'itemCount',
                                        'location' => new Location(new Position(75, 4, 12), new Position(86, 4, 23)),
                                    ],
                                    [
                                        'type' => Type::LITERAL,
                                        'value' => ' items',
                                        'location' => new Location(new Position(86, 4, 23), new Position(92, 4, 29)),
                                    ],
                                ],
                                'location' => new Location(new Position(74, 4, 11), new Position(93, 4, 30)),
                            ],
                        ],
                        'pluralType' => 'cardinal',
                        'location' => new Location(new Position(9, 1, 10), new Position(95, 5, 2)),
                    ],
                    [
                        'type' => Type::LITERAL,
                        'value' => '.',
                        'location' => new Location(new Position(95, 5, 2), new Position(96, 5, 3)),
                    ],
                ],
                'err' => null,
            ],
        ];

        yield 'many parameters, plural, select, with HTML' => [
            <<<'EOT'
I have {count, plural,
    one{a {
        gender, select,
            male{male}
            female{female}
            other{male}
        } <b>dog</b>
    }
    other{many dogs}} and {count, plural,
        one{a {
            gender, select,
                male{male}
                female{female}
                other{male}
            } <strong>cat</strong>
        }
        other{many cats}}
EOT,
            [
                'val' => [
                    [
                        'type' => Type::LITERAL,
                        'value' => 'I have ',
                        'location' => new Location(new Position(0, 1, 1), new Position(7, 1, 8)),
                    ],
                    [
                        'type' => Type::PLURAL,
                        'value' => 'count',
                        'offset' => 0,
                        'options' => [
                            'one' => [
                                'value' => [
                                    [
                                        'type' => Type::LITERAL,
                                        'value' => 'a ',
                                        'location' => new Location(new Position(31, 2, 9), new Position(33, 2, 11)),
                                    ],
                                    [
                                        'type' => Type::SELECT,
                                        'value' => 'gender',
                                        'options' => [
                                            'male' => [
                                                'value' => [
                                                    [
                                                        'type' => Type::LITERAL,
                                                        'value' => 'male',
                                                        'location' => new Location(new Position(76, 4, 18), new Position(80, 4, 22)),
                                                    ],
                                                ],
                                                'location' => new Location(new Position(75, 4, 17), new Position(81, 4, 23)),
                                            ],
                                            'female' => [
                                                'value' => [
                                                    [
                                                        'type' => Type::LITERAL,
                                                        'value' => 'female',
                                                        'location' => new Location(new Position(101, 5, 20), new Position(107, 5, 26)),
                                                    ],
                                                ],
                                                'location' => new Location(new Position(100, 5, 19), new Position(108, 5, 27)),
                                            ],
                                            'other' => [
                                                'value' => [
                                                    [
                                                        'type' => Type::LITERAL,
                                                        'value' => 'male',
                                                        'location' => new Location(new Position(127, 6, 19), new Position(131, 6, 23)),
                                                    ],
                                                ],
                                                'location' => new Location(new Position(126, 6, 18), new Position(132, 6, 24)),
                                            ],
                                        ],
                                        'location' => new Location(new Position(33, 2, 11), new Position(142, 7, 10)),
                                    ],
                                    [
                                        'type' => Type::LITERAL,
                                        'value' => " <b>dog</b>\n    ",
                                        'location' => new Location(new Position(142, 7, 10), new Position(158, 8, 5)),
                                    ],
                                ],
                                'location' => new Location(new Position(30, 2, 8), new Position(159, 8, 6)),
                            ],
                            'other' => [
                                'value' => [
                                    [
                                        'type' => Type::LITERAL,
                                        'value' => 'many dogs',
                                        'location' => new Location(new Position(170, 9, 11), new Position(179, 9, 20)),
                                    ],
                                ],
                                'location' => new Location(new Position(169, 9, 10), new Position(180, 9, 21)),
                            ],
                        ],
                        'pluralType' => 'cardinal',
                        'location' => new Location(new Position(7, 1, 8), new Position(181, 9, 22)),
                    ],
                    [
                        'type' => Type::LITERAL,
                        'value' => ' and ',
                        'location' => new Location(new Position(181, 9, 22), new Position(186, 9, 27)),
                    ],
                    [
                        'type' => Type::PLURAL,
                        'value' => 'count',
                        'location' => new Location(new Position(186, 9, 27), new Position(402, 17, 26)),
                        'offset' => 0,
                        'options' => [
                            'one' => [
                                'value' => [
                                    [
                                        'type' => Type::LITERAL,
                                        'value' => 'a ',
                                        'location' => new Location(new Position(214, 10, 13), new Position(216, 10, 15)),
                                    ],
                                    [
                                        'type' => Type::SELECT,
                                        'options' => [
                                            'male' => [
                                                'value' => [
                                                    [
                                                        'type' => Type::LITERAL,
                                                        'value' => 'male',
                                                        'location' => new Location(new Position(267, 12, 22), new Position(271, 12, 26)),
                                                    ],
                                                ],
                                                'location' => new Location(new Position(266, 12, 21), new Position(272, 12, 27)),
                                            ],
                                            'female' => [
                                                'value' => [
                                                    [
                                                        'type' => Type::LITERAL,
                                                        'value' => 'female',
                                                        'location' => new Location(new Position(296, 13, 24), new Position(302, 13, 30)),
                                                    ],
                                                ],
                                                'location' => new Location(new Position(295, 13, 23), new Position(303, 13, 31)),
                                            ],
                                            'other' => [
                                                'value' => [
                                                    [
                                                        'type' => Type::LITERAL,
                                                        'value' => 'male',
                                                        'location' => new Location(new Position(326, 14, 23), new Position(330, 14, 27)),
                                                    ],
                                                ],
                                                'location' => new Location(new Position(325, 14, 22), new Position(331, 14, 28)),
                                            ],
                                        ],
                                        'value' => 'gender',
                                        'location' => new Location(new Position(216, 10, 15), new Position(345, 15, 14)),
                                    ],
                                    [
                                        'type' => Type::LITERAL,
                                        'value' => " <strong>cat</strong>\n        ",
                                        'location' => new Location(new Position(345, 15, 14), new Position(375, 16, 9)),
                                    ],
                                ],
                                'location' => new Location(new Position(213, 10, 12), new Position(376, 16, 10)),
                            ],
                            'other' => [
                                'value' => [
                                    [
                                        'type' => Type::LITERAL,
                                        'value' => 'many cats',
                                        'location' => new Location(new Position(391, 17, 15), new Position(400, 17, 24)),
                                    ],
                                ],
                                'location' => new Location(new Position(390, 17, 14), new Position(401, 17, 25)),
                            ],
                        ],
                        'pluralType' => 'cardinal',
                    ],
                ],
                'err' => null,
            ],
        ];
    }

    public function testParseWithUnclosedBracket()
    {
        $intlMessageParser = new IntlMessageParser('Hello {name!');

        static::assertEquals([
            'val' => null,
            'err' => [
                'kind' => ErrorKind::MALFORMED_ARGUMENT,
                'location' => new Location(new Position(6, 1, 7), new Position(11, 1, 12)),
                'message' => 'Hello {name!',
            ],
        ], $intlMessageParser->parse());
    }
}
