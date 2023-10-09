<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

/**
 * @author Gábor Egyed <gabor.egyed@gmail.com>
 *
 * @experimental
 */
final class LiveCollectionTraitTest extends TestCase
{
    /** @dataProvider provideAddedItems */
    public function testAddCollectionItem(array $postedFormData, string $collectionFieldName, array $expectedFormData): void
    {
        $component = $this->createComponent($postedFormData);

        $component->addCollectionItem(PropertyAccess::createPropertyAccessor(), $collectionFieldName);

        self::assertSame($expectedFormData[$component->formName], $component->formValues);
    }

    /** @dataProvider provideRemovedItems */
    public function testRemoveCollectionItem(array $postedFormData, string $collectionFieldName, int $index, array $expectedFormData): void
    {
        $component = $this->createComponent($postedFormData);

        $component->removeCollectionItem(PropertyAccess::createPropertyAccessor(), $collectionFieldName, $index);

        self::assertSame($expectedFormData[$component->formName], $component->formValues);
    }

    public static function provideAddedItems(): iterable
    {
        yield 'unnamed parent form' => [
            [
                '' => [
                    'collectionName' => null,
                ],
            ],
            'collectionName',
            [
                '' => [
                    'collectionName' => [
                        0 => [],
                    ],
                ],
            ],
        ];
        yield 'named parent, null value' => [
            [
                'parentName' => [
                    'collectionName' => null,
                ],
            ],
            'parentName[collectionName]',
            [
                'parentName' => [
                    'collectionName' => [
                        0 => [],
                    ],
                ],
            ],
        ];
        yield 'named parent, empty array value' => [
            [
                'parentName' => [
                    'collectionName' => [],
                ],
            ],
            'parentName[collectionName]',
            [
                'parentName' => [
                    'collectionName' => [
                        0 => [],
                    ],
                ],
            ],
        ];
        yield 'deep array' => [
            [
                'foo' => [
                    'bar' => [
                        'baz' => [],
                    ],
                ],
            ],
            'foo[bar][baz]',
            [
                'foo' => [
                    'bar' => [
                        'baz' => [
                            0 => [],
                        ],
                    ],
                ],
            ],
        ];
        yield 'has items already' => [
            [
                'foo' => [
                    'bar' => [
                        0 => [],
                        1 => [],
                    ],
                ],
            ],
            'foo[bar]',
            [
                'foo' => [
                    'bar' => [
                        0 => [],
                        1 => [],
                        2 => [],
                    ],
                ],
            ],
        ];
    }

    public static function provideRemovedItems(): iterable
    {
        yield 'unnamed parent form' => [
            [
                '' => [
                    'collectionName' => [
                        0 => [],
                    ],
                ],
            ],
            'collectionName',
            0,
            [
                '' => [
                    'collectionName' => [],
                ],
            ],
        ];
        yield 'named parent' => [
            [
                'parentName' => [
                    'collectionName' => [
                        0 => [],
                    ],
                ],
            ],
            'parentName[collectionName]',
            0,
            [
                'parentName' => [
                    'collectionName' => [],
                ],
            ],
        ];
        yield 'deep array' => [
            [
                'foo' => [
                    'bar' => [
                        'baz' => [
                            0 => [],
                        ],
                    ],
                ],
            ],
            'foo[bar][baz]',
            0,
            [
                'foo' => [
                    'bar' => [
                        'baz' => [],
                    ],
                ],
            ],
        ];
        yield 'middle item' => [
            [
                'foo' => [
                    'bar' => [
                        0 => [],
                        1 => [],
                        2 => [],
                    ],
                ],
            ],
            'foo[bar]',
            1,
            [
                'foo' => [
                    'bar' => [
                        0 => [],
                        2 => [],
                    ],
                ],
            ],
        ];
    }

    private function createComponent(array $postedFormData)
    {
        $form = $this->createMock(FormInterface::class);
        $component = new class($form) {
            use LiveCollectionTrait;

            public function __construct(
                private FormInterface $theForm,
            ) {
            }

            protected function instantiateForm(): FormInterface
            {
                return $this->theForm;
            }
        };
        $component->formName = array_key_first($postedFormData);
        $component->formValues = $postedFormData[$component->formName];

        return $component;
    }
}
