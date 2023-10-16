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
use Symfony\UX\Translator\MessageParameters\Extractor\MessageParametersExtractor;

class MessageParametersExtractorTest extends TestCase
{
    /**
     * @dataProvider provideExtract
     */
    public function testExtract(string $message, array $expectedParameters): void
    {
        $messageParametersExtractor = new MessageParametersExtractor();

        static::assertEquals($messageParametersExtractor->extract($message), $expectedParameters);
    }

    public function provideExtract()
    {
        yield [
            'Symfony is great!',
            [],
        ];

        yield [
            'Symfony is %what%!',
            ['%what%' => ['type' => 'string']],
        ];

        yield [
            '%framework% is %what%!',
            [
                '%framework%' => ['type' => 'string'],
                '%what%' => ['type' => 'string'],
            ],
        ];

        yield [
            '%framework% have more than %years% years!',
            [
                '%framework%' => ['type' => 'string'],
                '%years%' => ['type' => 'string'],
            ],
        ];

        yield [
            '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples',
            ['%count%' => ['type' => 'number']],
        ];

        yield [
            'There is 1 apple|There are %count% apples',
            ['%count%' => ['type' => 'number']],
        ];

        yield [
            'You must select at least {{ limit }} choice.|You must select at least {{ limit }} choices.',
            ['{{ limit }}' => ['type' => 'string']],
        ];
    }
}
