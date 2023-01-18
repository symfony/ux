<?php

namespace Symfony\UX\LiveComponent\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\Util\PropsDataHelper;

class PropsDataHelperTest extends TestCase
{
    /**
     * @dataProvider getPropertyPathTestCases
     */
    public function testExpandPropertyPathDataToArray(array $input, array $expectedOutput): void
    {
        $actual = PropsDataHelper::expandToFrontendArray($input);
        $this->assertSame($expectedOutput, $actual);
    }

    public function getPropertyPathTestCases(): iterable
    {
        foreach ($this->getTestCases() as $key => $testCase) {
            yield 'forward_'.$key => $testCase;
        }
    }

    /**
     * @dataProvider getPropertyPathTestCasesReversed
     */
    public function testFlattenArrayToPropertyPathData(array $input, array $expectedOutput): void
    {
        $actual = PropsDataHelper::flattenToBackendArray($input);
        $this->assertSame($expectedOutput, $actual);
    }

    public function getPropertyPathTestCasesReversed(): iterable
    {
        foreach ($this->getTestCases() as $key => $testCase) {
            yield 'reverse_'.$key => [
                $testCase[1],
                $testCase[0],
            ];
        }
    }

    public function getTestCases(): iterable
    {
        yield 'identity_string_no_writable_paths' => [
            ['firstName' => 'Ryan'],
            ['firstName' => 'Ryan'],
        ];

        yield 'identity_string_with_writable_paths' => [
            [
                'product' => '5',
                'product.name' => 'marshmallows',
            ],
            [
                'product' => [
                    '@id' => '5',
                    'name' => 'marshmallows',
                ],
            ],
        ];

        yield 'identity_array_no_writable_paths' => [
            [
                'product' => [
                    'name' => 'marshmallows',
                ],
            ],
            [
                'product' => [
                    'name' => 'marshmallows',
                ],
            ],
        ];

        yield 'identity_array_with_writable_paths' => [
            [
                'product' => [
                    'name' => 'marshmallows',
                    'description' => 'delicious',
                ],
                'product.price' => '25.99',
                'product.description' => 'delicious',
            ],
            [
                'product' => [
                    '@id' => [
                        'name' => 'marshmallows',
                        'description' => 'delicious',
                    ],
                    'price' => '25.99',
                    'description' => 'delicious',
                ],
            ],
        ];

        yield 'identity_with_deep_paths' => [
            [
                'product' => '5',
                'product.name' => 'marshmallows',
                'product.category.title' => 'campfire food',
            ],
            [
                'product' => [
                    '@id' => '5',
                    'name' => 'marshmallows',
                    'category' => [
                        'title' => 'campfire food',
                    ],
                ],
            ],
        ];

        yield 'identity_indexed_array_of_strings' => [
            [
                'tags' => [
                    0 => 'foo',
                    1 => 'bar',
                ],
            ],
            [
                'tags' => [
                    0 => 'foo',
                    1 => 'bar',
                ],
            ],
        ];

        yield 'identity_indexed_array_of_objects' => [
            [
                'tags' => [
                    0 => [
                        'name' => 'foo',
                    ],
                    1 => [
                        'name' => 'bar',
                    ],
                ],
            ],
            [
                'tags' => [
                    0 => [
                        'name' => 'foo',
                    ],
                    1 => [
                        'name' => 'bar',
                    ],
                ],
            ],
        ];

        yield 'identity_indexed_array_of_objects_with_writable' => [
            [
                'tags' => [
                    0 => [
                        'name' => 'foo',
                    ],
                    1 => [
                        'name' => 'bar',
                    ],
                ],
                'tags.0.name' => 'foo',
            ],
            [
                'tags' => [
                    '@id' => [
                        0 => [
                            'name' => 'foo',
                        ],
                        1 => [
                            'name' => 'bar',
                        ],
                    ],
                    0 => [
                        'name' => 'foo',
                    ],
                ],
            ],
        ];
    }
}
