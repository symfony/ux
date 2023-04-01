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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Exception\HydrationException;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component2;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component3;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\BlogPostWithSerializationContext;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Embeddable2;
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

    private function executeHydrationTestCase(callable $testFactory, int $minPhpVersion = null): void
    {
        if (null !== $minPhpVersion && $minPhpVersion > \PHP_VERSION_ID) {
            $this->markTestSkipped(sprintf('Test requires PHP version %s or higher.', $minPhpVersion));
        }

        // lazily create the test case so each case can prep its data with an isolated container
        $testBuilder = $testFactory();
        if (!$testBuilder instanceof HydrationTest) {
            throw new \InvalidArgumentException('Test case callable must return a HydrationTest instance.');
        }

        $metadataFactory = self::getContainer()->get('ux.live_component.metadata_factory');
        \assert($metadataFactory instanceof LiveComponentMetadataFactory);
        $testCase = $testBuilder->getTest($metadataFactory);

        // keep a copy of the original, empty component object for hydration later
        $originalComponentWithData = clone $testCase->component;
        $componentAfterHydration = clone $testCase->component;
        $hydratedComponent2 = clone $testCase->component;

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
        $this->assertArrayNotHasKey('@attributes', $dehydratedProps->getProps());

        if (null !== $testCase->expectedDehydratedProps) {
            $expectedDehydratedProps = $testCase->expectedDehydratedProps;
            // add checksum to make comparison happy
            $expectedDehydratedProps['@checksum'] = $dehydratedProps->getProps()['@checksum'];
            $this->assertEquals($expectedDehydratedProps, $dehydratedProps->getProps(), 'Dehydrated props do not match expected.');
        }

        if ($testCase->expectHydrationException) {
            $this->expectException($testCase->expectHydrationException);
            if ($testCase->expectHydrationExceptionMessage) {
                $this->expectExceptionMessageMatches($testCase->expectHydrationExceptionMessage);
            }
        }

        if ($testCase->beforeHydrationCallable) {
            ($testCase->beforeHydrationCallable)();
        }

        $originalPropsToSend = $testCase->changedOriginalProps ?? $dehydratedProps->getProps();
        // mimic sending over the wire, which can subtle change php types
        $originalPropsToSend = json_decode(json_encode($originalPropsToSend), true);

        $this->hydrator()->hydrate(
            $componentAfterHydration,
            $originalPropsToSend,
            $testCase->updatedProps,
            $liveMetadata
        );

        if (null !== $testCase->assertObjectAfterHydrationCallable) {
            ($testCase->assertObjectAfterHydrationCallable)($componentAfterHydration);
        }

        $dehydratedProps2 = $this->hydrator()->dehydrate(
            $componentAfterHydration,
            new ComponentAttributes([]), // not worried about testing these here
            $liveMetadata,
        );
        $this->hydrator()->hydrate(
            $hydratedComponent2,
            $dehydratedProps2->getProps(),
            [],
            $liveMetadata
        );
        $this->assertEquals($componentAfterHydration, $hydratedComponent2, 'After another round of (de)hydration, things still match');
    }

    /**
     * @dataProvider provideDehydrationHydrationTests
     */
    public function testCanDehydrateAndHydrateComponentWithTestCases(callable $testFactory, ?int $minPhpVersion = null): void
    {
        $this->executeHydrationTestCase($testFactory, $minPhpVersion);
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
                ->userChangesOriginalPropsTo(['firstName' => 'Kevin'])
                ->expectsExceptionDuringHydration(BadRequestHttpException::class, '/checksum/i');
        }];

        yield 'string: changing writable field works' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public string $firstName;
            })
                ->mountWith(['firstName' => 'Ryan'])
                ->assertDehydratesTo(['firstName' => 'Ryan'])
                ->userUpdatesProps(['firstName' => 'Kevin'])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame('Kevin', $object->firstName);
                })
            ;
        }];

        yield 'float: precision change to the frontend works ok' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public float $price;
            })
                // when the 123.00 float/double is json encoded, it becomes the
                // integer 123. If not handled correctly, this can cause a checksum
                // failure: the checksum is originally calculated with the float
                // 123.00, but then when the props are sent back to the server,
                // the float is converted to an integer 123, which causes the checksum
                // to fail.
                ->mountWith(['price' => 123.00])
                ->assertDehydratesTo(['price' => 123.00])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(123.00, $object->price);
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
                ->userUpdatesProps(['entity1' => $entityNext->id])
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
                ->userUpdatesProps(['entity1' => $entityNext->id])
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
                    'product' => $product->id,
                    'product.name' => $product->name,
                ])
                ->userUpdatesProps([
                    'product.name' => 'real chicken',
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
                #[LiveProp(useSerializerForHydration: true)]
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
            // set props: but these will be lost
            $product->name = 'original name';
            $product->price = 333;

            return HydrationTest::create(new class() {
                // make a path writable, just to be tricky
                #[LiveProp(writable: ['price'])]
                public ProductFixtureEntity $product;
            })
                ->mountWith(['product' => $product])
                ->assertDehydratesTo([
                    'product' => [],
                    'product.price' => 333,
                ])
                ->userUpdatesProps([
                    'product.price' => 1000,
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertNull($object->product->id);
                    // set value is lost: we simply reinstantiate the entity
                    $this->assertSame('', $object->product->name);
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
                ->userUpdatesProps([
                    'foods' => ['apple', 'chips'],
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
                ->userUpdatesProps(['options' => [
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

        yield 'Associative array: fully writable allows partial changes' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public array $options = [];
            })
                ->mountWith(['options' => [
                    'show' => 'Arrested development',
                    'character' => 'Michael Bluth',
                ]])
                ->userUpdatesProps([
                    // instead of replacing the entire array, you can change
                    // just one key on the array, since it's fully writable
                    'options.character' => 'Buster Bluth',
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(
                        [
                            'show' => 'Arrested development',
                            'character' => 'Buster Bluth',
                        ],
                        $object->options
                    );
                });
        }];

        yield 'Associative array: fully writable allows deep partial changes' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true, fieldName: 'invoice')]
                public array $formData = [];
            })
                ->mountWith(['formData' => [
                    'number' => '123',
                    'lineItems' => [
                        ['name' => 'item1', 'quantity' => 4, 'price' => 100],
                        ['name' => 'item2', 'quantity' => 2, 'price' => 200],
                        ['name' => 'item3', 'quantity' => 1, 'price' => 1000],
                    ],
                ]])
                ->assertDehydratesTo(['invoice' => [
                    'number' => '123',
                    'lineItems' => [
                        ['name' => 'item1', 'quantity' => 4, 'price' => 100],
                        ['name' => 'item2', 'quantity' => 2, 'price' => 200],
                        ['name' => 'item3', 'quantity' => 1, 'price' => 1000],
                    ],
                ]])
                ->userUpdatesProps([
                    // invoice is used as the field name
                    'invoice.lineItems.0.quantity' => 5,
                    'invoice.lineItems.1.price' => 300,
                    'invoice.number' => '456',
                    // tricky: overriding the entire array
                    'invoice.lineItems.2' => ['name' => 'item3_updated', 'quantity' => 2, 'price' => 2000],
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame([
                        'number' => '456',
                        'lineItems' => [
                            ['name' => 'item1', 'quantity' => 5, 'price' => 100],
                            ['name' => 'item2', 'quantity' => 2, 'price' => 300],
                            ['name' => 'item3_updated', 'quantity' => 2, 'price' => 2000],
                        ]],
                        $object->formData
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
                ->assertDehydratesTo([
                    'options' => [
                        'show' => 'Arrested development',
                        'character' => 'Michael Bluth',
                    ],
                    'options.character' => 'Michael Bluth',
                ])
                ->userUpdatesProps([
                    'options.character' => 'George Michael Bluth',
                ])
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
                ->assertDehydratesTo([
                    'options' => [
                        'show' => 'Arrested development',
                        'character' => 'Michael Bluth',
                    ],
                    'options.character' => 'Michael Bluth',
                ])
                ->userChangesOriginalPropsTo(['options' => [
                    'show' => 'Simpsons',
                    'character' => 'Michael Bluth',
                ]])
                ->expectsExceptionDuringHydration(BadRequestHttpException::class, '/checksum/i');
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
                ->assertDehydratesTo([
                    'stuff' => ['details' => [
                        'key1' => 'bar',
                        'key2' => 'baz',
                    ]],
                    'stuff.details.key1' => 'bar',
                ])
                ->userUpdatesProps(['stuff.details.key1' => 'changed key1'])
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
                ->assertDehydratesTo([
                    'stuff' => ['details' => [
                        'key1' => 'bar',
                        'key2' => 'baz',
                    ]],
                    'stuff.details' => ['key1' => 'bar', 'key2' => 'baz'],
                ])
                ->userUpdatesProps([
                    'stuff.details' => ['key1' => 'changed key1', 'new_key' => 'new value'],
                ])
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

        yield 'Array with objects: (de)hydrates correctly' => [function () {
            $prod1 = create(ProductFixtureEntity::class, ['name' => 'item1'])->object();
            $prod2 = new ProductFixtureEntity();
            $prod3 = create(ProductFixtureEntity::class, ['name' => 'item3'])->object();

            return HydrationTest::create(new class() {
                #[LiveProp()]
                /** @var \Symfony\UX\LiveComponent\Tests\Fixtures\Entity\ProductFixtureEntity[] */
                public $products = [];
            })
                ->mountWith(['products' => [$prod1, $prod2, $prod3]])
                ->assertDehydratesTo([
                    'products' => [$prod1->id, [], $prod3->id],
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame(
                        'item1',
                        $object->products[0]->name
                    );
                    // the non-persisted one is simply reinstantiated
                    $this->assertInstanceOf(
                        ProductFixtureEntity::class,
                        $object->products[1],
                    );
                    $this->assertNull($object->products[1]->id);
                    $this->assertSame(
                        'item3',
                        $object->products[2]->name
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
                ->userUpdatesProps(['int' => 1])
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
                ->userUpdatesProps([
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
                ->userUpdatesProps(['int' => 99999])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertNull($object->int);
                })
            ;
        }, 80100];

        yield 'Object: using custom normalizer (de)hydrates correctly' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(useSerializerForHydration: true)]
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
                #[LiveProp(useSerializerForHydration: true)]
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

        yield 'Updating non-writable path is rejected' => [function () {
            $product = new ProductFixtureEntity();
            $product->name = 'original name';
            $product->price = 333;

            return HydrationTest::create(new class() {
                #[LiveProp(writable: ['price'])]
                public ProductFixtureEntity $product;
            })
                ->mountWith(['product' => $product])
                ->userUpdatesProps([
                    'product.name' => 'will cause an explosion',
                ])
                ->expectsExceptionDuringHydration(HydrationException::class, '/The model "product\.name" was sent for update, but it is not writable\. Try adding "writable\: \[\'name\'\]" to the \$product property in/')
            ;
        }];

        yield 'Updating non-writable property is rejected' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp()]
                public string $name;
            })
                ->mountWith(['name' => 'Ryan'])
                ->userUpdatesProps([
                    'name' => 'will cause an explosion',
                ])
                ->expectsExceptionDuringHydration(HydrationException::class, '/The model "name" was sent for update, but it is not writable\. Try adding "writable\: true" to the \$name property in/')
            ;
        }];

        yield 'Context: Pass (de)normalization context' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(serializationContext: ['groups' => 'foo'])]
                public string $name;

                #[LiveProp(useSerializerForHydration: true, serializationContext: ['groups' => 'foo'])]
                public \DateTimeInterface $createdAt;

                #[LiveProp(useSerializerForHydration: true, serializationContext: ['groups' => 'the_serialization_group'])]
                public BlogPostWithSerializationContext $blogPost;
            })
                ->mountWith([
                    'name' => 'Ryan',
                    'createdAt' => new \DateTime('2023-03-05 9:23', new \DateTimeZone('America/New_York')),
                    'blogPost' => new BlogPostWithSerializationContext('the_title', 'the_body', 2500),
                ])
                ->assertDehydratesTo([
                    'name' => 'Ryan',
                    'createdAt' => '2023-03-05T09:23:00-05:00',
                    'blogPost' => [
                        // price is not in the normalization groups
                        'title' => 'the_title',
                        'body' => 'the_body',
                    ],
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame('Ryan', $object->name);
                    $this->assertSame('2023-03-05 09:23:00', $object->createdAt->format('Y-m-d H:i:s'));
                    $this->assertSame('the_title', $object->blogPost->title);
                    $this->assertSame('the_body', $object->blogPost->body);
                    // price wasn't even sent, so it's null
                    $this->assertSame(0, $object->blogPost->price);
                })
            ;
        }];

        yield 'It is valid to dehydrate to a fully-writable array' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true, dehydrateWith: 'dehydrateDate', hydrateWith: 'hydrateDate')]
                public \DateTime $createdAt;

                public function __construct()
                {
                    $this->createdAt = new \DateTime();
                }

                public function dehydrateDate()
                {
                    return [
                        'year' => $this->createdAt->format('Y'),
                        'month' => $this->createdAt->format('m'),
                    ];
                }

                public function hydrateDate($data)
                {
                    return \DateTime::createFromFormat(
                        'Y-m',
                        sprintf('%s-%s', $data['year'], $data['month'])
                    );
                }
            })
                ->mountWith([
                   'createdAt' => new \DateTime('2023-03-05 9:23', new \DateTimeZone('America/New_York')),
               ])
                ->assertDehydratesTo([
                   'createdAt' => ['year' => 2023, 'month' => 3],
               ])
                ->userUpdatesProps([
                   'createdAt' => ['year' => 2024, 'month' => 4],
               ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame('2024-04', $object->createdAt->format('Y-m'));
                })
            ;
        }];

        yield 'Use the format option to control the date format' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true, format: 'Y-m-d')]
                public \DateTime $createdAt;

                public function __construct()
                {
                    $this->createdAt = new \DateTime();
                }
            })
                ->mountWith([
                   'createdAt' => new \DateTime('2023-03-05 9:23', new \DateTimeZone('America/New_York')),
               ])
                ->assertDehydratesTo([
                   'createdAt' => '2023-03-05',
               ])
                ->userUpdatesProps([
                   'createdAt' => '2024-04-06',
               ])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame('2024-04-06', $object->createdAt->format('Y-m-d'));
                })
            ;
        }];
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

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessageMatches('/The model path "createdAt" was sent an invalid data type "array"/');

        $updatedProps = ['createdAt' => ['year' => 2023, 'month' => 2]];

        $this->hydrator()->hydrate(
            $component,
            $dehydratedProps->getProps(),
            $updatedProps,
            $this->createLiveMetadata($component),
        );
    }

    /**
     * @dataProvider provideInvalidHydrationTests
     */
    public function testInvalidTypeHydration(callable $testFactory, int $minPhpVersion = null): void
    {
        $this->executeHydrationTestCase($testFactory, $minPhpVersion);
    }

    public function provideInvalidHydrationTests(): iterable
    {
        yield 'invalid_types_string_to_number_uses_coerced' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public int $count;
            })
                ->mountWith(['count' => 1])
                ->userUpdatesProps(['count' => 'pretzels'])
                ->assertObjectAfterHydration(function (object $object) {
                    // pretzels is coerced to 0
                    $this->assertSame(0, $object->count);
                });
        }];

        yield 'invalid_types_array_to_string_is_rejected' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public string $name;
            })
                ->mountWith(['name' => 'Ryan'])
                ->userUpdatesProps(['name' => ['pretzels', 'nonsense']])
                ->assertObjectAfterHydration(function (object $object) {
                    $this->assertSame('Ryan', $object->name);
                });
        }];

        yield 'invalid_types_writable_path_values_not_accepted' => [function () {
            $product = create(ProductFixtureEntity::class, [
                'name' => 'oranges',
                'price' => 199,
            ])->object();

            return HydrationTest::create(new class() {
                #[LiveProp(writable: ['name', 'price'])]
                public ProductFixtureEntity $product;
            })
                ->mountWith(['product' => $product])
                ->userUpdatesProps([
                    'product.name' => ['pretzels', 'nonsense'],
                    'product.price' => 'bananas',
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    // changes rejected
                    $this->assertSame('oranges', $object->product->name);
                    $this->assertSame(199, $object->product->price);
                });
        }];

        yield 'invalid_types_enum_with_an_invalid_value' => [function () {
            return HydrationTest::create(new class() {
                #[LiveProp(writable: true)]
                public ?IntEnum $nullableInt = null;

                #[LiveProp(writable: true)]
                public IntEnum $nonNullableInt;
            })
                ->mountWith([
                    'nullableInt' => IntEnum::LOW,
                    'nonNullableInt' => IntEnum::LOW,
                ])
                ->assertDehydratesTo([
                    'nullableInt' => 1,
                    'nonNullableInt' => 1,
                ])
                ->userUpdatesProps([
                    // not a real option
                    'nullableInt' => 500,
                    'nonNullableInt' => 500,
                ])
                ->assertObjectAfterHydration(function (object $object) {
                    // nullable int becomes null
                    $this->assertNull($object->nullableInt);
                    // non-nullable change is rejected (1=LOW)
                    $this->assertSame(1, $object->nonNullableInt->value);
                });
        }, 80100];
    }

    public function testHydrationFailsIfChecksumMissing(): void
    {
        $component = $this->getComponent('component1');

        $this->expectException(BadRequestHttpException::class);

        $this->hydrateComponent($component, 'component1', []);
    }

    public function testHydrationFailsOnChecksumMismatch(): void
    {
        $component = $this->getComponent('component1');

        $this->expectException(BadRequestHttpException::class);

        $this->hydrateComponent($component, 'component1', ['@checksum' => 'invalid']);
    }

    public function testHydrationTakeUpdatedParentPropsIntoAccount(): void
    {
        $component = new class() {
            #[LiveProp(writable: true)]
            public string $name = 'Ryan';

            #[LiveProp(updateFromParent: true)]
            public bool $shouldUppercase = false;
        };

        $freshComponent = clone $component;

        $liveMetadata = $this->createLiveMetadata($component);
        $dehydrated = $this->hydrator()->dehydrate(
            $component,
            new ComponentAttributes([]),
            $liveMetadata
        );
        $updatedFromParentData = ['shouldUppercase' => true];
        $updatedFromParentData = $this->hydrator()->addChecksumToData($updatedFromParentData);

        $this->hydrator()->hydrate(
            $freshComponent,
            $dehydrated->getProps(),
            [], // updated data
            $liveMetadata,
            $updatedFromParentData
        );
        // keeps original, dehydrated value
        $this->assertSame('Ryan', $freshComponent->name);
        // updated from parent
        $this->assertTrue($freshComponent->shouldUppercase);
    }

    public function testHydrationWithUpdatesParentPropsAndBadChecksumFails(): void
    {
        $component = new class() {
            #[LiveProp(updateFromParent: true)]
            public string $name = 'Ryan';
        };

        $freshComponent = clone $component;

        $liveMetadata = $this->createLiveMetadata($component);
        $dehydrated = $this->hydrator()->dehydrate(
            $component,
            new ComponentAttributes([]),
            $liveMetadata
        );
        $updatedFromParentData = ['name' => 'Kevin'];
        $updatedFromParentData = $this->hydrator()->addChecksumToData($updatedFromParentData);
        // change the data again: now the checksum is wrong!
        $updatedFromParentData['name'] = 'Fabien';

        $this->expectException(BadRequestHttpException::class);
        $this->hydrator()->hydrate(
            $freshComponent,
            $dehydrated->getProps(),
            [], // updated data
            $liveMetadata,
            $updatedFromParentData
        );
    }

    public function testPreDehydrateAndPostHydrateHooksCalled(): void
    {
        $mounted = $this->mountComponent('component2');

        /** @var Component2 $component */
        $component = $mounted->getComponent();

        $this->assertFalse($component->preDehydrateCalled);
        $this->assertFalse($component->postHydrateCalled);

        $dehydrated = $this->dehydrateComponent($mounted);

        $this->assertTrue($component->preDehydrateCalled);
        $this->assertFalse($component->postHydrateCalled);

        /** @var Component2 $component */
        $component = $this->getComponent('component2');

        $this->assertFalse($component->preDehydrateCalled);
        $this->assertFalse($component->postHydrateCalled);

        $this->hydrateComponent($component, $mounted->getName(), $dehydrated->getProps());

        $this->assertFalse($component->preDehydrateCalled);
        $this->assertTrue($component->postHydrateCalled);
    }

    public function testCorrectlyUsesCustomFrontendNameInDehydrateAndHydrate(): void
    {
        $mounted = $this->mountComponent('component3', ['prop1' => 'value1', 'prop2' => 'value2']);
        $dehydratedProps = $this->dehydrateComponent($mounted)->getProps();

        $this->assertArrayNotHasKey('prop1', $dehydratedProps);
        $this->assertArrayNotHasKey('prop2', $dehydratedProps);
        $this->assertArrayHasKey('myProp1', $dehydratedProps);
        $this->assertArrayHasKey('myProp2', $dehydratedProps);
        $this->assertSame('value1', $dehydratedProps['myProp1']);
        $this->assertSame('value2', $dehydratedProps['myProp2']);

        /** @var Component3 $component */
        $component = $this->getComponent('component3');

        $this->hydrateComponent($component, $mounted->getName(), $dehydratedProps);

        $this->assertSame('value1', $component->prop1);
        $this->assertSame('value2', $component->prop2);
    }

    public function testCanDehydrateAndHydrateComponentsWithAttributes(): void
    {
        $mounted = $this->mountComponent('with_attributes', $attributes = ['class' => 'foo', 'value' => null]);

        $this->assertSame($attributes, $mounted->getAttributes()->all());

        $dehydratedProps = $this->dehydrateComponent($mounted)->getProps();

        $this->assertArrayHasKey('@attributes', $dehydratedProps);
        $this->assertSame($attributes, $dehydratedProps['@attributes']);

        $actualAttributes = $this->hydrateComponent($this->getComponent('with_attributes'), $mounted->getName(), $dehydratedProps);

        $this->assertSame($attributes, $actualAttributes->all());
    }

    public function testCanDehydrateAndHydrateComponentsWithEmptyAttributes(): void
    {
        $mounted = $this->mountComponent('with_attributes');

        $this->assertSame([], $mounted->getAttributes()->all());

        $dehydratedProps = $this->dehydrateComponent($mounted)->getProps();

        $this->assertArrayNotHasKey('_attributes', $dehydratedProps);

        $actualAttributes = $this->hydrateComponent($this->getComponent('with_attributes'), $mounted->getName(), $dehydratedProps);

        $this->assertSame([], $actualAttributes->all());
    }

    /**
     * @dataProvider falseyValueProvider
     */
    public function testCoerceFalseyValuesForScalarTypes($prop, $value, $expected): void
    {
        $dehydratedProps = $this->dehydrateComponent($this->mountComponent('scalar_types'))->getProps();

        $updatedProps = [$prop => $value];
        $hydratedComponent = $this->getComponent('scalar_types');
        $this->hydrateComponent($hydratedComponent, 'scalar_types', $dehydratedProps, $updatedProps);

        $this->assertSame($expected, $hydratedComponent->$prop);
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
        yield 'fooey-o-booey-todo' => ['nullableBool', '   ', null];
    }

    private function createLiveMetadata(object $component): LiveComponentMetadata
    {
        $reflectionClass = new \ReflectionClass($component);
        $metadataFactory = self::getContainer()->get('ux.live_component.metadata_factory');
        \assert($metadataFactory instanceof LiveComponentMetadataFactory);
        $livePropsMetadata = $metadataFactory->createPropMetadatas($reflectionClass);

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
    private array $updatedProps = [];
    private ?\Closure $assertObjectAfterHydrationCallable = null;
    private ?\Closure $beforeHydrationCallable = null;
    private ?array $changedOriginalProps = null;
    private ?string $expectedHydrationException = null;
    private ?string $expectHydrationExceptionMessage = null;

    private function __construct(
        private object $component,
    ) {
    }

    public static function create(object $component): self
    {
        return new self($component);
    }

    public function mountWith(array $props): self
    {
        $this->inputProps = $props;

        return $this;
    }

    public function assertDehydratesTo(array $expectDehydratedProps): self
    {
        $this->expectedDehydratedProps = $expectDehydratedProps;

        return $this;
    }

    public function userUpdatesProps(array $updatedProps): self
    {
        $this->updatedProps = $updatedProps;

        return $this;
    }

    public function userChangesOriginalPropsTo(array $newProps): self
    {
        $this->changedOriginalProps = $newProps;

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

    public function getTest(LiveComponentMetadataFactory $metadataFactory): HydrationTestCase
    {
        $reflectionClass = new \ReflectionClass($this->component);

        return new HydrationTestCase(
            $this->component,
            new LiveComponentMetadata(
                new ComponentMetadata(['key' => '__testing']),
                $metadataFactory->createPropMetadatas($reflectionClass),
            ),
            $this->inputProps,
            $this->expectedDehydratedProps,
            $this->updatedProps,
            $this->changedOriginalProps,
            $this->assertObjectAfterHydrationCallable,
            $this->beforeHydrationCallable,
            $this->expectedHydrationException,
            $this->expectHydrationExceptionMessage
        );
    }

    public function expectsExceptionDuringHydration(string $exceptionClass, string $exceptionMessage = null): self
    {
        $this->expectedHydrationException = $exceptionClass;
        $this->expectHydrationExceptionMessage = $exceptionMessage;

        return $this;
    }
}

class HydrationTestCase
{
    public function __construct(
        public object $component,
        public LiveComponentMetadata $liveMetadata,
        public array $inputProps,
        public ?array $expectedDehydratedProps,
        public array $updatedProps,
        public ?array $changedOriginalProps,
        public ?\Closure $assertObjectAfterHydrationCallable,
        public ?\Closure $beforeHydrationCallable,
        public ?string $expectHydrationException,
        public ?string $expectHydrationExceptionMessage,
    ) {
    }
}
