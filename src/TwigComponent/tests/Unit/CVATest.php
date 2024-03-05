<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\CVA;

/**
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 */
class CVATest extends TestCase
{
    /**
     * @dataProvider recipeProvider
     */
    public function testRecipes(array $recipe, array $recipes, string $expected): void
    {
        $recipeClass = new CVA($recipe['base'] ?? '', $recipe['variants'] ?? [], $recipe['compounds'] ?? [], $recipe['defaultVariants'] ?? []);

        $this->assertEquals($expected, $recipeClass->resolve($recipes));
    }

    public function testApply(): void
    {
        $recipe = new CVA('font-semibold border rounded', [
            'colors' => [
                'primary' => 'text-primary',
                'secondary' => 'text-secondary',
            ],
            'sizes' => [
                'sm' => 'text-sm',
                'md' => 'text-md',
                'lg' => 'text-lg',
            ],
        ], [
            [
                'colors' => ['primary'],
                'sizes' => ['sm'],
                'class' => 'text-red-500',
            ],
        ]);

        $this->assertEquals('font-semibold border rounded text-primary text-sm text-red-500', $recipe->apply(['colors' => 'primary', 'sizes' => 'sm']));
    }

    public function testApplyWithNullString(): void
    {
        $recipe = new CVA('font-semibold border rounded', [
            'colors' => [
                'primary' => 'text-primary',
                'secondary' => 'text-secondary',
            ],
            'sizes' => [
                'sm' => 'text-sm',
                'md' => 'text-md',
                'lg' => 'text-lg',
            ],
        ], [
            [
                'colors' => ['primary'],
                'sizes' => ['sm'],
                'class' => 'text-red-500',
            ],
        ]);

        $this->assertEquals('font-semibold border rounded text-primary text-sm text-red-500 flex justify-center', $recipe->apply(['colors' => 'primary', 'sizes' => 'sm'], 'flex', null, 'justify-center'));
    }

    public static function recipeProvider(): iterable
    {
        yield 'base null' => [
            ['variants' => [
                'colors' => [
                    'primary' => 'text-primary',
                    'secondary' => 'text-secondary',
                ],
                'sizes' => [
                    'sm' => 'text-sm',
                    'md' => 'text-md',
                    'lg' => 'text-lg',
                ],
            ]],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'text-primary text-sm',
        ];

        yield 'base empty' => [
            [
                'base' => '',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ]],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'text-primary text-sm',
        ];

        yield 'no recipes match' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
            ],
            ['colors' => 'red', 'sizes' => 'test'],
            'font-semibold border rounded',
        ];

        yield 'simple variants' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm',
        ];

        yield 'simple variants with custom' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
            ],
            ['colors' => 'secondary', 'sizes' => 'md'],
            'font-semibold border rounded text-secondary text-md',
        ];

        yield 'compound variants' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['primary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm text-red-500',
        ];

        yield 'multiple compound variants' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['primary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                    [
                        'colors' => ['primary'],
                        'sizes' => ['md'],
                        'class' => 'text-blue-500',
                    ],
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm text-red-500',
        ];

        yield 'compound with multiple variants' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['primary', 'secondary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm text-red-500',
        ];

        yield 'compound doesn\'t match' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['danger', 'secondary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm',
        ];
        yield 'default variables' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                    'rounded' => [
                        'sm' => 'rounded-sm',
                        'md' => 'rounded-md',
                        'lg' => 'rounded-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['danger', 'secondary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'primary',
                    'sizes' => 'sm',
                    'rounded' => 'md',
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm'],
            'font-semibold border rounded text-primary text-sm rounded-md',
        ];
        yield 'default variables all overwrite' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                    'rounded' => [
                        'sm' => 'rounded-sm',
                        'md' => 'rounded-md',
                        'lg' => 'rounded-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['danger', 'secondary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'primary',
                    'sizes' => 'sm',
                    'rounded' => 'md',
                ],
            ],
            ['colors' => 'primary', 'sizes' => 'sm', 'rounded' => 'lg'],
            'font-semibold border rounded text-primary text-sm rounded-lg',
        ];
        yield 'default variables without matching variants' => [
            [
                'base' => 'font-semibold border rounded',
                'variants' => [
                    'colors' => [
                        'primary' => 'text-primary',
                        'secondary' => 'text-secondary',
                    ],
                    'sizes' => [
                        'sm' => 'text-sm',
                        'md' => 'text-md',
                        'lg' => 'text-lg',
                    ],
                    'rounded' => [
                        'sm' => 'rounded-sm',
                        'md' => 'rounded-md',
                        'lg' => 'rounded-lg',
                    ],
                ],
                'compounds' => [
                    [
                        'colors' => ['danger', 'secondary'],
                        'sizes' => ['sm'],
                        'class' => 'text-red-500',
                    ],
                ],
                'defaultVariants' => [
                    'colors' => 'primary',
                    'sizes' => 'sm',
                    'rounded' => 'md',
                ],
            ],
            [],
            'font-semibold border rounded text-primary text-sm rounded-md',
        ];
    }
}
