<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component2;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component3;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Embeddable2;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\HoldsDateAndEntity;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\HoldsStringEnum;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Money;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Temperature;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Embeddable1;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity2;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\ProductFixtureEntity;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\EmptyStringEnum;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\IntEnum;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\StringEnum;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\ZeroIntEnum;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\create;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentHydratorTest extends KernelTestCase
{
    use Factories;
    use LiveComponentTestHelper;
    use ResetDatabase;

    private function executeHydrationTestCase(HydrationTestCase $testCase): void
    {
        // keep a copy of the original, empty component object for hydration later
        $originalComponentWithData = clone $testCase->component;
        $componentAfterHydration = clone $testCase->component;

        $liveMetadata = $testCase->liveMetadata;

        $this->factory()->mountFromObject(
            $originalComponentWithData,
            $testCase->inputProps,
            $liveMetadata->getComponentMetadata()
        );

        $dehydratedProps = $this->hydrator()->dehydrate(
            $originalComponentWithData,
            new ComponentAttributes([]), // not worried about testing these here
            $liveMetadata,
        );

        // this is empty, so won't be here
        $this->assertArrayNotHasKey('@attributes', $dehydratedProps);

        if (null !== $testCase->expectedDehydratedProps) {
            $expectedDehydratedProps = $testCase->expectedDehydratedProps;
            // add checksum to make comparison happy
            $expectedDehydratedProps['@checksum'] = $dehydratedProps['@checksum'];
            $this->assertEquals($expectedDehydratedProps, $dehydratedProps, 'Dehydrated props do not match expected.');
        }

        // grab the data the user is sending, or use $dehydratedProps if not set
        $dataForHydration = $testCase->sentDataForHydration ?? $dehydratedProps;
        // make sure checksum is set
        $dataForHydration['@checksum'] = $dehydratedProps['@checksum'];

        if ($testCase->expectHydrationFailsChecksum) {
            $this->expectException(UnprocessableEntityHttpException::class);
            $this->expectExceptionMessageMatches('/checksum/i');
        }

        if ($testCase->beforeHydrationCallable) {
            ($testCase->beforeHydrationCallable)();
        }

        $this->hydrator()->hydrate($componentAfterHydration, $dataForHydration, $liveMetadata);

        if (null !== $testCase->assertObjectAfterHydrationCallable) {
            ($testCase->assertObjectAfterHydrationCallable)($componentAfterHydration);
        }
    }

    /**
     * @dataProvider provideDehydrationHydrationTests
     */
    public function testCanDehydrateAndHydrateComponentWithTestCases(callable $testFactory, ?int $minPHPVersion = null): void
    {
        if (null !== $minPHPVersion && $minPHPVersion > \PHP_VERSION_ID) {
            $this->markTestSkipped(sprintf('Test requires PHP version %s or higher.', $minPHPVersion));
        }

        // lazily create the test case so each case can prep its data with an isolated container
        $testBuilder = $testFactory();
        if (!$testBuilder instanceof HydrationTest) {
            throw new \InvalidArgumentException('Test case callable must return a HydrationTest instance.');
        }

        $this->executeHydrationTestCase($testBuilder->getTest(), $minPHPVersion);
    }

    public function provideDehydrationHydrationTests(): iterable
    {
        yield 'string: (de)hydrates correctly' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp()]
                public string $firstName;
            })
                ->mountWith(['firstName' => 'Ryan'])
                ->assertDehydratesTo(['firstName' => 'Ryan'])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame('Ryan', $object->firstName);
                });
        }];

        yield 'string: changing non-writable causes checksum fail' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp()]
                public string $firstName;
            })
                ->mountWith(['firstName' => 'Ryan'])
                ->assertDehydratesTo(['firstName' => 'Ryan'])
                ->userChangesDataTo(['firstName' => 'Kevin'])
                ->expectHydrationFailsChecksum();
        }];

        yield 'string: changing writable field works' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public string $firstName;
            })
                ->mountWith(['firstName' => 'Ryan'])
                ->assertDehydratesTo(['firstName' => 'Ryan'])
                ->userChangesDataTo(['firstName' => 'Kevin'])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame('Kevin', $object->firstName);
                })
            ;
        }];

        yield 'DateTime: (de)hydrates correctly' => [function () {
            $date = new \DateTime('2023-03-05 9:23', new \DateTimeZone('America/New_York'));

            return HydrationTest::create(new class() {
                #[LiveProp()]
                public \DateTime $createdAt;
            })
                ->mountWith(['createdAt' => $date])
                ->assertDehydratesTo(['createdAt' => '2023-03-05T09:23:00-05:00'])
                ->assertObjectAfterHydration(function (object $object) use ($date) {
                    $this->assertSame(
                        $date->format('U'),
                        $object->createdAt->format('U')
                    );
                })
            ;
        }];

        yield 'Persisted entity: (de)hydration works correctly to/from id' => [function () {
            $entity1 = create(Entity1::class)->object();
            \assert($entity1 instanceof Entity1);

            return HydrationTest::create(new class() {
                #[LiveProp()]
                public Entity1 $entity1;
            })
                ->mountWith(['entity1' => $entity1])
                ->assertDehydratesTo(['entity1' => $entity1->id])
                ->assertObjectAfterHydration(function (object $object) use ($entity1) {
                    $this->assertSame(
                        $entity1->id,
                        $object->entity1->id
                    );
                })
            ;
        }];

        yield 'Persisted entity: non-writable cannot be changed' => [function () {
            $entityFoo = create(Entity1::class)->object();
            $entityBar = create(Entity1::class)->object();
            \assert($entityFoo instanceof Entity1);
            \assert($entityBar instanceof Entity1);

            return HydrationTest::create(new class() {
                #[LiveProp()]
                public Entity1 $entity1;
            })
                ->mountWith(['entity1' => $entityFoo])
                ->userChangesDataTo(['entity1' => $entityBar->id])
                ->expectHydrationFailsChecksum()
            ;
        }];

        yield 'Persisted entity: writable CAN be changed via id' => [function () {
            $entityOriginal = create(Entity1::class)->object();
            $entityNext = create(Entity1::class)->object();
            \assert($entityOriginal instanceof Entity1);
            \assert($entityNext instanceof Entity1);

            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public Entity1 $entity1;
            })
                ->mountWith(['entity1' => $entityOriginal])
                ->userChangesDataTo(['entity1' => $entityNext->id])
                ->assertObjectAfterHydration(function (object $object) use ($entityNext) {
                    $this->assertSame(
                        $entityNext->id,
                        $object->entity1->id
                    );
                })
            ;
        }];

        yield 'Persisted entity: writable (via IDENTITY constant) CAN be changed via id' => [function () {
            $entityOriginal = create(Entity1::class)->object();
            $entityNext = create(Entity1::class)->object();
            \assert($entityOriginal instanceof Entity1);
            \assert($entityNext instanceof Entity1);

            return HydrationTest::create(new class() {
                #[LiveProp(writable: [LiveProp::IDENTITY])]
                public Entity1 $entity1;
            })
                ->mountWith(['entity1' => $entityOriginal])
                ->userChangesDataTo(['entity1' => $entityNext->id])
                ->assertObjectAfterHydration(function (object $object) use ($entityNext) {
                    $this->assertSame(
                        $entityNext->id,
                        $object->entity1->id
                    );
                })
            ;
        }];

        yield 'Persisted entity: non-writable identity but with writable paths updates correctly' => [function () {
            $product = create(ProductFixtureEntity::class, [
                'name' => 'Rubber Chicken',
            ])->object();

            return HydrationTest::create(new class() {
                #[LiveProp(writable: ['name'])]
                public ProductFixtureEntity $product;
            })
                ->mountWith(['product' => $product])
                ->assertDehydratesTo([
                    'product' => [
                        '@id' => $product->id,
                        'name' => $product->name,
                    ],
                ])
                ->userChangesDataTo([
                    'product' => [
                        '@id' => $product->id,
                        'name' => 'real chicken',
                    ],
                ])
                ->assertObjectAfterHydration(function (object $object) use ($product) {
                    $this->assertSame(
                        $product->id,
                        $object->product->id
                    );
                    $this->assertSame(
                        'real chicken',
                        $object->product->name
                    );
                })
            ;
        }];

        yield 'Persisted entity: deleting entity between dehydration and hydration sets it to null' => [function () {
            $product = create(ProductFixtureEntity::class);

            return HydrationTest::create(new class() {
                // test that event the writable path doesn't cause problems
                #[LiveProp(writable: ['name'])]
                public ?ProductFixtureEntity $product;
            })
                ->mountWith(['product' => $product->object()])
                ->beforeHydration(function () use ($product) {
                    $product->remove();
                })
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertNull(
                        $object->product
                    );
                })
            ;
        }];

        yield 'Persisted entity: with custom_normalizer and embeddable (de)hydrates correctly' => [function () {
            $entity2 = create(Entity2::class, ['embedded1' => new Embeddable1('bar'), 'embedded2' => new Embeddable2('baz')])->object();

            return HydrationTest::create(new class() {
                #[LiveProp]
                public Entity2 $entity2;
            })
                ->mountWith(['entity2' => $entity2])
                ->assertDehydratesTo([
                    // Entity2 has a custom normalizer
                    'entity2' => 'entity2:'.$entity2->id,
                ])
                ->assertObjectAfterHydration(function (object $object) use ($entity2) {
                    $this->assertSame($entity2->id, $object->entity2->id);
                    $this->assertSame('bar', $object->entity2->embedded1->name);
                    $this->assertSame('baz', $object->entity2->embedded2->name);
                })
            ;
        }];

        yield 'Non-Persisted entity: non-writable (de)hydrates correctly' => [function () {
            $product = new ProductFixtureEntity();
            $product->name = 'original name';
            $product->price = 333;

            return HydrationTest::create(new class() {
                // make a path writable, just to be tricky
                #[LiveProp(writable: ['price'])]
                public ProductFixtureEntity $product;
            })
                ->mountWith(['product' => $product])
                ->assertDehydratesTo(['product' => [
                    '@id' => [
                        'id' => null,
                        'name' => 'original name',
                        'price' => 333,
                    ],
                    'price' => 333,
                ]])
                ->userChangesDataTo(['product' => [
                    '@id' => [
                        'id' => null,
                        'name' => 'original name',
                        'price' => 333,
                    ],
                    'name' => 'will be ignored',
                    'price' => 1000,
                ]])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertNull($object->product->id);
                    // from the denormalizing process
                    $this->assertSame('original name', $object->product->name);
                    // from the writable path sent by the user
                    $this->assertSame(1000, $object->product->price);
                })
            ;
        }];

        yield 'Index array: (de)hydrates correctly' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp()]
                public array $foods = [];
            })
                ->mountWith(['foods' => ['banana', 'popcorn']])
                ->assertDehydratesTo(['foods' => ['banana', 'popcorn']])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(
                        ['banana', 'popcorn'],
                        $object->foods
                    );
                })
            ;
        }];

        yield 'Index array: writable allows all keys to change' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public array $foods = [];
            })
                ->mountWith(['foods' => ['banana', 'popcorn']])
                ->userChangesDataTo([
                    'foods' => [
                        0 => 'apple',
                        1 => 'chips',
                    ],
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(
                        ['apple', 'chips'],
                        $object->foods
                    );
                })
            ;
        }];

        yield 'Associative array: (de)hyrates correctly' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp()]
                public array $options = [];
            })
                ->mountWith(['options' => [
                    'show' => 'Arrested development',
                    'character' => 'Michael Bluth',
                    'quote' => 'I\'ve made a huge mistake',
                ]])
                ->assertDehydratesTo(['options' => [
                    'show' => 'Arrested development',
                    'character' => 'Michael Bluth',
                    'quote' => 'I\'ve made a huge mistake',
                ]])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(
                        [
                            'show' => 'Arrested development',
                            'character' => 'Michael Bluth',
                            'quote' => 'I\'ve made a huge mistake',
                        ],
                        $object->options
                    );
                });
        }];

        yield 'Associative array: fully writable allows anything to change' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public array $options = [];
            })
                ->mountWith(['options' => [
                    'show' => 'Arrested development',
                    'character' => 'Michael Bluth',
                ]])
                ->assertDehydratesTo(['options' => [
                    'show' => 'Arrested development',
                    'character' => 'Michael Bluth',
                ]])
                ->userChangesDataTo(['options' => [
                    'show' => 'Simpsons',
                    'quote' => 'I didn\'t do it',
                ]])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(
                        [
                            'show' => 'Simpsons',
                            'quote' => 'I didn\'t do it',
                        ],
                        $object->options
                    );
                });
        }];

        yield 'Associative array: writable paths allow those to change' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: ['character'])]
                public array $options = [];
            })
                ->mountWith(['options' => [
                    'show' => 'Arrested development',
                    'character' => 'Michael Bluth',
                ]])
                ->assertDehydratesTo(['options' => [
                    '@id' => [
                        'show' => 'Arrested development',
                        'character' => 'Michael Bluth',
                    ],
                    'character' => 'Michael Bluth',
                ]])
                ->userChangesDataTo(['options' => [
                    '@id' => [
                        'show' => 'Arrested development',
                        'character' => 'Michael Bluth',
                    ],
                    'character' => 'George Michael Bluth',
                ]])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(
                        [
                            'show' => 'Arrested development',
                            'character' => 'George Michael Bluth',
                        ],
                        $object->options
                    );
                });
        }];

        yield 'Associative array: writable paths do not allow OTHER keys to change' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: ['character'])]
                public array $options = [];
            })
                ->mountWith(['options' => [
                    'show' => 'Arrested development',
                    'character' => 'Michael Bluth',
                ]])
                ->assertDehydratesTo(['options' => [
                    '@id' => [
                        'show' => 'Arrested development',
                        'character' => 'Michael Bluth',
                    ],
                    'character' => 'Michael Bluth',
                ]])
                ->userChangesDataTo(['options' => [
                    '@id' => [
                        'show' => 'Simpsons',
                        'character' => 'Michael Bluth',
                    ],
                    'character' => 'George Michael Bluth',
                ]])
                ->expectHydrationFailsChecksum();
        }];

        yield 'Associative array: support for multiple levels of writable path' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: ['details.key1'])]
                public array $stuff = [];
            })
                ->mountWith(['stuff' => ['details' => [
                    'key1' => 'bar',
                    'key2' => 'baz',
                ]]])
                ->assertDehydratesTo(['stuff' => [
                    '@id' => ['details' => [
                        'key1' => 'bar',
                        'key2' => 'baz',
                    ]],
                    'details' => ['key1' => 'bar'],
                ]])
                ->userChangesDataTo(['stuff' => [
                    '@id' => ['details' => [
                        'key1' => 'bar',
                        'key2' => 'baz',
                    ]],
                    'details' => ['key1' => 'changed key1'],
                ]])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(['details' => [
                        'key1' => 'changed key1',
                        'key2' => 'baz',
                    ]], $object->stuff);
                })
            ;
        }];

        yield 'Associative array: a writable path can itself be an array' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: ['details'])]
                public array $stuff = [];
            })
                ->mountWith(['stuff' => ['details' => [
                    'key1' => 'bar',
                    'key2' => 'baz',
                ]]])
                ->assertDehydratesTo(['stuff' => [
                    '@id' => ['details' => [
                        'key1' => 'bar',
                        'key2' => 'baz',
                    ]],
                    'details' => ['key1' => 'bar', 'key2' => 'baz'],
                ]])
                ->userChangesDataTo(['stuff' => [
                    '@id' => ['details' => [
                        'key1' => 'bar',
                        'key2' => 'baz',
                    ]],
                    'details' => ['key1' => 'changed key1', 'new_key' => 'new value'],
                ]])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(['details' => [
                        'key1' => 'changed key1',
                        'new_key' => 'new value',
                    ]], $object->stuff);
                })
            ;
        }];

        yield 'Empty array: (de)hydrates correctly' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp()]
                public array $foods = [];
            })
                ->mountWith([])
                ->assertDehydratesTo(['foods' => []])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(
                        [],
                        $object->foods
                    );
                })
            ;
        }];

        yield 'Enum: null remains null' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp()]
                public ?IntEnum $int = null;

                #[LiveProp()]
                public ?StringEnum $string = null;
            })
                ->mountWith([])
                ->assertDehydratesTo(['int' => null, 'string' => null])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertNull($object->int);
                    $this->assertNull($object->string);
                })
            ;
        }, 80100];

        yield 'Enum: (de)hydrates correctly' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp()]
                public ?IntEnum $int = null;

                #[LiveProp()]
                public ?StringEnum $string = null;
            })
                ->mountWith(['int' => IntEnum::HIGH, 'string' => StringEnum::ACTIVE])
                ->assertDehydratesTo(['int' => 10, 'string' => 'active'])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertInstanceOf(IntEnum::class, $object->int);
                    $this->assertSame(10, $object->int->value);
                    $this->assertInstanceOf(StringEnum::class, $object->string);
                    $this->assertSame('active', $object->string->value);
                })
            ;
        }, 80100];

        yield 'Enum: writable enums can be changed' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public ?IntEnum $int = null;
            })
                ->mountWith(['int' => IntEnum::HIGH])
                ->userChangesDataTo(['int' => 1])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(1, $object->int->value);
                })
            ;
        }, 80100];

        yield 'Enum: null-like enum values are handled correctly' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public ?ZeroIntEnum $zeroInt = null;

                #[LiveProp(writable: true)]
                public ?ZeroIntEnum $zeroInt2 = null;

                #[LiveProp(writable: true)]
                public ?EmptyStringEnum $emptyString = null;
            })
                ->mountWith([])
                ->assertDehydratesTo([
                    'zeroInt' => null,
                    'zeroInt2' => null,
                    'emptyString' => null,
                ])
                ->userChangesDataTo([
                    'zeroInt' => 0,
                    'zeroInt2' => '0',
                    'emptyString' => '',
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(ZeroIntEnum::ZERO, $object->zeroInt);
                    $this->assertSame(ZeroIntEnum::ZERO, $object->zeroInt2);
                    $this->assertSame(EmptyStringEnum::EMPTY, $object->emptyString);
                })
            ;
        }, 80100];

        yield 'Enum: nullable enum with invalid value sets to null' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public ?IntEnum $int = null;
            })
                ->mountWith(['int' => IntEnum::HIGH])
                ->assertDehydratesTo(['int' => 10])
                ->userChangesDataTo(['int' => 99999])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertNull($object->int);
                })
            ;
        }, 80100];

        yield 'Object: using custom normalizer (de)hydrates correctly' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp]
                public Money $money;
            })
                ->mountWith(['money' => new Money(500, 'CAD')])
                ->assertDehydratesTo([
                    'money' => '500|CAD',
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(500, $object->money->amount);
                    $this->assertSame('CAD', $object->money->currency);
                })
            ;
        }];

        yield 'Object: dehydrates to array works correctly' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp]
                public Temperature $temperature;
            })
                ->mountWith(['temperature' => new Temperature(30, 'C')])
                ->assertDehydratesTo([
                    'temperature' => [
                        'degrees' => 30,
                        'uom' => 'C',
                    ],
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(30, $object->temperature->degrees);
                    $this->assertSame('C', $object->temperature->uom);
                })
            ;
        }];

        yield 'Object: Embeddable object (de)hydrates correctly' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp]
                public Embeddable1 $embeddable1;
            })
                ->mountWith(['embeddable1' => new Embeddable1('foo')])
                ->assertDehydratesTo([
                    'embeddable1' => [
                        'name' => 'foo',
                    ],
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame('foo', $object->embeddable1->name);
                })
            ;
        }];

        yield 'Object: writable property that requires (de)normalization works correctly' => [function () {
            $product = create(ProductFixtureEntity::class, [
                'name' => 'foo',
                'price' => 100,
            ])->object();
            $product2 = create(ProductFixtureEntity::class, [
                'name' => 'bar',
                'price' => 500,
            ])->object();
            \assert($product instanceof ProductFixtureEntity);
            $holdsDate = new HoldsDateAndEntity(
                new \DateTime('2023-03-05 9:23', new \DateTimeZone('America/New_York')),
                $product
            );

            return HydrationTest::create(new class() {
                #[LiveProp(writable: ['createdAt', 'product'])]
                public HoldsDateAndEntity $holdsDate;
            })
                ->mountWith(['holdsDate' => $holdsDate])
                ->assertDehydratesTo([
                    'holdsDate' => [
                        '@id' => [
                            'createdAt' => '2023-03-05T09:23:00-05:00',
                            'product' => $product->id,
                        ],
                        'createdAt' => '2023-03-05T09:23:00-05:00',
                        'product' => $product->id,
                    ],
                ])
                ->userChangesDataTo([
                    'holdsDate' => [
                        '@id' => [
                            'createdAt' => '2023-03-05T09:23:00-05:00',
                            'product' => $product->id,
                        ],
                        // change these: their values should dehydrate and be used
                        'createdAt' => '2022-01-01T09:23:00-05:00',
                        'product' => $product2->id,
                    ],
                ])
                ->assertObjectAfterHydration(function (object $object) use ($product2) {
                    $this->assertSame(
                        '2022-01-01 09:23:00',
                        $object->holdsDate->createdAt->format('Y-m-d H:i:s')
                    );
                    $this->assertSame(
                        $product2->id,
                        $object->holdsDate->product->id,
                    );
                })
            ;
        }];

        yield 'Object: writable property that with invalid enum property coerced to null' => [function () {
            $holdsStringEnum = new HoldsStringEnum(StringEnum::ACTIVE);

            return HydrationTest::create(new class() {
                #[LiveProp(writable: ['stringEnum'])]
                public HoldsStringEnum $holdsStringEnum;
            })
                ->mountWith(['holdsStringEnum' => $holdsStringEnum])
                ->assertDehydratesTo([
                    'holdsStringEnum' => [
                        '@id' => [
                            'stringEnum' => 'active',
                        ],
                        'stringEnum' => 'active',
                    ],
                ])
                ->userChangesDataTo([
                    'holdsStringEnum' => [
                        '@id' => [
                            'stringEnum' => 'active',
                        ],
                        'stringEnum' => 'not_real',
                    ],
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertNull(
                        $object->holdsStringEnum->stringEnum,
                    );
                })
            ;
        }, 80100];
    }

    public function testWritableObjectsDehydratedToArrayIsNotAllowed(): void
    {
        $component = new class() {
            #[LiveProp(writable: true, dehydrateWith: 'dehydrateDate')]
            public \DateTime $createdAt;

            public function __construct()
            {
                $this->createdAt = new \DateTime();
            }

            public function dehydrateDate()
            {
                return ['year' => 2023, 'month' => 02];
            }
        };

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessageMatches('/The LiveProp path "createdAt" is an object that was dehydrated to an array/');
        $this->expectExceptionMessageMatches('/You probably want to set writable to only the properties on your class that should be writable/');
        $this->hydrator()->dehydrate(
            $component,
            new ComponentAttributes([]),
            $this->createLiveMetadata($component)
        );
    }

    public function testWritablePathObjectsDehydratedToArrayIsNotAllowed(): void
    {
        $component = new class() {
            #[LiveProp(writable: ['product'])]
            public HoldsDateAndEntity $holdsDateAndEntity;

            public function __construct()
            {
                $this->holdsDateAndEntity = new HoldsDateAndEntity(
                    new \DateTime(),
                    // non-persisted entity will dehydrate to an array
                    new ProductFixtureEntity(),
                );
            }
        };

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessageMatches('/The LiveProp path "holdsDateAndEntity.product" is an object that was dehydrated to an array/');
        $this->hydrator()->dehydrate(
            $component,
            new ComponentAttributes([]),
            $this->createLiveMetadata($component)
        );
    }

    public function testPassingArrayToWritablePropForHydrationIsNotAllowed(): void
    {
        $component = new class() {
            #[LiveProp(writable: true)]
            public \DateTime $createdAt;

            public function __construct()
            {
                $this->createdAt = new \DateTime();
            }
        };

        $dehydratedProps = $this->hydrator()->dehydrate(
            $component,
            new ComponentAttributes([]),
            $this->createLiveMetadata($component)
        );

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessageMatches('/The model path "createdAt" was sent as an array, but this could not be hydrated to an object as that is not allowed/');

        $dehydratedProps['createdAt'] = ['year' => 2023, 'month' => 02];

        $this->hydrator()->hydrate(
            $component,
            $dehydratedProps,
            $this->createLiveMetadata($component),
        );
    }

    public function testPassingArrayToWritablePathForHydrationIsNotAllowed(): void
    {
        $component = new class() {
            #[LiveProp(writable: ['product'])]
            public HoldsDateAndEntity $holdsDateAndEntity;

            public function __construct()
            {
                $this->holdsDateAndEntity = new HoldsDateAndEntity(
                    new \DateTime(),
                    create(ProductFixtureEntity::class)->object()
                );
            }
        };

        $dehydratedProps = $this->hydrator()->dehydrate(
            $component,
            new ComponentAttributes([]),
            $this->createLiveMetadata($component)
        );

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessageMatches('/The model path "holdsDateAndEntity.product" was sent as an array, but this could not be hydrated to an object as that is not allowed/');

        $dehydratedProps['holdsDateAndEntity']['product'] = ['name' => 'new name'];

        $this->hydrator()->hydrate(
            $component,
            $dehydratedProps,
            $this->createLiveMetadata($component),
        );
    }

    /**
     * @dataProvider provideInvalidHydrationTests
     */
    public function testInvalidTypeHydration()
    {
        $this->markTestIncomplete('TODO');
    }

    public function provideInvalidHydrationTests(): iterable
    {
        // TODO
        yield 'enum_with_an_invalid_value_TODO' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public ?IntEnum $int = null;
            })
                ->mountWith([])
                ->userChangesDataTo([
                    'int' => 'not-real-value',
                ])
            ;
        }, 80100];

        // TODO: invalid values for type (e.g. int to string type-hint)
        // TODO invalid value for enum
        //      A) including some non-string, non-integer value
        //      B) Passing string to an int backed
        //      C) Passing correct type, but not in the enum
        // TODO (and maybe in a different test) that you can't dehydrate an object to
        //      an array AND be writable

        // TODO: check that this is not allowed - and error comes from hydration system
        // and will result in a 400 level code
        yield 'writable_persisted_entity_can_be_replaced_with_new_object' => [function () {
            $product = create(ProductFixtureEntity::class)->object();
            \assert($product instanceof ProductFixtureEntity);

            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public ProductFixtureEntity $product;
            })
                ->mountWith(['product' => $product])
                ->userChangesDataTo(['product' => [
                    'name' => 'Totally new name',
                    'price' => 1000,
                ]])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertNull($object->product->id);
                    // special system only creates a new object
                    $this->assertSame('', $object->product->name);
                    $this->assertSame(0, $object->product->price);
                })
            ;
        }];
    }

    public function testHydrationFailsIfChecksumMissing(): void
    {
        $component = $this->getComponent('component1');

        $this->expectException(\RuntimeException::class);

        $this->hydrateComponent($component, [], 'component1');
    }

    public function testHydrationFailsOnChecksumMismatch(): void
    {
        $component = $this->getComponent('component1');

        $this->expectException(\RuntimeException::class);

        $this->hydrateComponent($component, ['@checksum' => 'invalid'], 'component1');
    }

    public function testPreDehydrateAndPostHydrateHooksCalled(): void
    {
        $mounted = $this->mountComponent('component2');

        /** @var Component2 $component */
        $component = $mounted->getComponent();

        $this->assertFalse($component->preDehydrateCalled);
        $this->assertFalse($component->postHydrateCalled);

        $data = $this->dehydrateComponent($mounted);

        $this->assertTrue($component->preDehydrateCalled);
        $this->assertFalse($component->postHydrateCalled);

        /** @var Component2 $component */
        $component = $this->getComponent('component2');

        $this->assertFalse($component->preDehydrateCalled);
        $this->assertFalse($component->postHydrateCalled);

        $this->hydrateComponent($component, $data, $mounted->getName());

        $this->assertFalse($component->preDehydrateCalled);
        $this->assertTrue($component->postHydrateCalled);
    }

    public function testCorrectlyUsesCustomFrontendNameInDehydrateAndHydrate(): void
    {
        $mounted = $this->mountComponent('component3', ['prop1' => 'value1', 'prop2' => 'value2']);
        $dehydrated = $this->dehydrateComponent($mounted);

        $this->assertArrayNotHasKey('prop1', $dehydrated);
        $this->assertArrayNotHasKey('prop2', $dehydrated);
        $this->assertArrayHasKey('myProp1', $dehydrated);
        $this->assertArrayHasKey('myProp2', $dehydrated);
        $this->assertSame('value1', $dehydrated['myProp1']);
        $this->assertSame('value2', $dehydrated['myProp2']);

        /** @var Component3 $component */
        $component = $this->getComponent('component3');

        $this->hydrateComponent($component, $dehydrated, $mounted->getName());

        $this->assertSame('value1', $component->prop1);
        $this->assertSame('value2', $component->prop2);
    }

    public function testCanDehydrateAndHydrateComponentsWithAttributes(): void
    {
        $mounted = $this->mountComponent('with_attributes', $attributes = ['class' => 'foo', 'value' => null]);

        $this->assertSame($attributes, $mounted->getAttributes()->all());

        $dehydrated = $this->dehydrateComponent($mounted);

        $this->assertArrayHasKey('@attributes', $dehydrated);
        $this->assertSame($attributes, $dehydrated['@attributes']);

        $actualAttributes = $this->hydrateComponent($this->getComponent('with_attributes'), $dehydrated, $mounted->getName());

        $this->assertSame($attributes, $actualAttributes->all());
    }

    public function testCanDehydrateAndHydrateComponentsWithEmptyAttributes(): void
    {
        $mounted = $this->mountComponent('with_attributes');

        $this->assertSame([], $mounted->getAttributes()->all());

        $dehydrated = $this->dehydrateComponent($mounted);

        $this->assertArrayNotHasKey('_attributes', $dehydrated);

        $actualAttributes = $this->hydrateComponent($this->getComponent('with_attributes'), $dehydrated, $mounted->getName());

        $this->assertSame([], $actualAttributes->all());
    }

    /**
     * @dataProvider falseyValueProvider
     */
    public function testCoerceFalseyValuesForScalarTypes($prop, $value, $expected): void
    {
        $dehydratedProps = $this->dehydrateComponent($this->mountComponent('scalar_types'));

        $dehydratedProps[$prop] = $value;

        $hydrated = $this->getComponent('scalar_types');
        $this->hydrateComponent($hydrated, $dehydratedProps, 'scalar_types');

        $this->assertSame($expected, $hydrated->$prop);
    }

    public static function falseyValueProvider(): iterable
    {
        yield ['int', '', 0];
        yield ['int', '   ', 0];
        yield ['int', 'apple', 0];
        yield ['float', '', 0.0];
        yield ['float', '   ', 0.0];
        yield ['float', 'apple', 0.0];
        yield ['bool', '', false];
        yield ['bool', '   ', false];

        yield ['nullableInt', '', null];
        yield ['nullableInt', '   ', null];
        yield ['nullableInt', 'apple', 0];
        yield ['nullableFloat', '', null];
        yield ['nullableFloat', '   ', null];
        yield ['nullableFloat', 'apple', 0.0];
        yield ['nullableBool', '', null];
        yield ['nullableBool', '   ', null];
    }

    private function createLiveMetadata(object $component): LiveComponentMetadata
    {
        $reflectionClass = new \ReflectionClass($component);
        $livePropsMetadata = LiveComponentMetadataFactory::createPropMetadatas($reflectionClass);

        return new LiveComponentMetadata(
            new ComponentMetadata(['key' => '__testing']),
            $livePropsMetadata,
        );
    }
}

class HydrationTest
{
    private array $inputProps;
    private ?array $expectedDehydratedProps = null;
    private ?array $sentDataForHydration = null;
    private ?\Closure $assertObjectAfterHydrationCallable = null;
    private ?\Closure $beforeHydrationCallable = null;
    private bool $expectHydrationFailsChecksum = false;

    private function __construct(
        private object $component,
        private array $propMetadatas,
    ) {
    }

    public static function create(object $component): self
    {
        $reflectionClass = new \ReflectionClass($component);

        return new self($component, LiveComponentMetadataFactory::createPropMetadatas($reflectionClass));
    }

    public function mountWith(array $props): self
    {
        $this->inputProps = $props;

        return $this;
    }

    public function assertDehydratesTo(array $expectDehydrated): self
    {
        $this->expectedDehydratedProps = $expectDehydrated;

        return $this;
    }

    public function userChangesDataTo(array $sentDataForHydration): self
    {
        $this->sentDataForHydration = $sentDataForHydration;

        return $this;
    }

    public function assertObjectAfterHydration(callable $assertCallable): self
    {
        $this->assertObjectAfterHydrationCallable = $assertCallable;

        return $this;
    }

    public function beforeHydration(callable $beforeHydrationCallable): self
    {
        $this->beforeHydrationCallable = $beforeHydrationCallable;

        return $this;
    }

    public function expectHydrationFailsChecksum(): self
    {
        $this->expectHydrationFailsChecksum = true;

        return $this;
    }

    public function getTest(): HydrationTestCase
    {
        return new HydrationTestCase(
            $this->component,
            new LiveComponentMetadata(
                new ComponentMetadata(['key' => '__testing']),
                $this->propMetadatas,
            ),
            $this->inputProps,
            $this->expectedDehydratedProps,
            $this->sentDataForHydration,
            $this->assertObjectAfterHydrationCallable,
            $this->beforeHydrationCallable,
            $this->expectHydrationFailsChecksum,
        );
    }
}

class HydrationTestCase
{
    public function __construct(
        public object $component,
        public LiveComponentMetadata $liveMetadata,
        public array $inputProps,
        public ?array $expectedDehydratedProps,
        public ?array $sentDataForHydration,
        public ?\Closure $assertObjectAfterHydrationCallable,
        public ?\Closure $beforeHydrationCallable,
        public bool $expectHydrationFailsChecksum,
    ) {
    }
}
