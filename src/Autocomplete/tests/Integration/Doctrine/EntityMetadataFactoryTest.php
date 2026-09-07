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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadata;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadataFactory;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Product;

class EntityMetadataFactoryTest extends KernelTestCase
{
    public function testItSuccessfullyCreatesMetadata(): void
    {
        /** @var EntityMetadataFactory $factory */
        $factory = self::getContainer()->get('ux.autocomplete.entity_metadata_factory');
        $metadata = $factory->create(Product::class);
        $this->assertInstanceOf(EntityMetadata::class, $metadata);
    }
}
