<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Integration\Doctrine;

use Doctrine\DBAL\Types\Types;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadata;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadataFactory;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Product;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\ProductFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class EntityMetadataTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testGetAllPropertyNames(): void
    {
        $this->assertSame(
            ['id', 'name', 'description', 'price', 'isEnabled'],
            $this->getMetadata()->getAllPropertyNames()
        );
    }

    public function testIsAssociation(): void
    {
        $metadata = $this->getMetadata();
        $this->assertFalse($metadata->isAssociation('name'));
        $this->assertTrue($metadata->isAssociation('category'));
    }

    public function testGetIdValue(): void
    {
        $product = ProductFactory::createOne();
        $this->assertEquals($product->getId(), $this->getMetadata()->getIdValue($product->object()));
    }

    public function testGetPropertyDataType(): void
    {
        $metadata = $this->getMetadata();
        $this->assertSame(Types::STRING, $metadata->getPropertyDataType('name'));
        // ORM 2.*  ClassMetadataInfo::MANY_TO_ONE
        // ORM 3.*  ClassMetadata::MANY_TO_ONE
        $this->assertEquals(2, $metadata->getPropertyDataType('category'));
    }

    public function testGetFieldMetadata(): void
    {
        $metadata = $this->getMetadata();
        $nameMetadata = $metadata->getFieldMetadata('name');
        $expected = [
            'fieldName' => 'name',
            'type' => 'string',
            'scale' => null,
            'length' => null,
            'unique' => false,
            'nullable' => false,
            'precision' => null,
            'columnName' => 'name',
        ];
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $nameMetadata);
            $this->assertSame($value, $nameMetadata[$key]);
        }
    }

    public function testGetAssociationMetadata(): void
    {
        $metadata = $this->getMetadata();
        $expected = [
            'fieldName' => 'category',
            'inversedBy' => 'products',
            'targetEntity' => 'Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Category',
            'fetch' => 2,
            'isOwningSide' => true,
            'sourceEntity' => 'Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Product',
            'sourceToTargetKeyColumns' => [
                'category_id' => 'id',
            ],
            'targetToSourceKeyColumns' => [
                'id' => 'category_id',
            ],
            'orphanRemoval' => false,
        ];

        $metadata = $metadata->getAssociationMetadata('category');

        foreach ($expected as $key => $val) {
            $this->assertArrayHasKey($key, $metadata);
            if (!\is_array($val)) {
                $this->assertEquals($val, $metadata[$key]);
                continue;
            }
            foreach ($val as $k => $v) {
                $this->assertArrayHasKey($k, $metadata[$key]);
                $this->assertEquals($v, $metadata[$key][$k]);
            }
        }

        $this->assertArrayHasKey('joinColumns', $metadata);
        $this->assertCount(1, $metadata['joinColumns']);
        $expectedJoinColumn = [
            'name' => 'category_id',
            'columnDefinition' => null,
            // Doctrine 3.0 removed
            // 'fieldName' => 'category_id',
            'unique' => false,
            'nullable' => true,
            'referencedColumnName' => 'id',
        ];
        $this->assertArrayHasKey(0, $metadata['joinColumns']);
        $columnMetadata = $metadata['joinColumns'][0];
        foreach ($expectedJoinColumn as $key => $val) {
            $this->assertArrayHasKey($key, $columnMetadata);
            // Doctrine 3.0 changed the way it determines 'nullable' for join columns
            if ('nullable' === $key) {
                $this->assertIsBool($columnMetadata[$key]);
                continue;
            }
            $this->assertSame($val, $columnMetadata[$key]);
        }
    }

    public function testIsEmbeddedClassProperty(): void
    {
        // TODO
        $this->markTestIncomplete();
    }

    private function getMetadata(): EntityMetadata
    {
        /** @var EntityMetadataFactory $factory */
        $factory = self::getContainer()->get('ux.autocomplete.entity_metadata_factory');

        return $factory->create(Product::class);
    }
}
