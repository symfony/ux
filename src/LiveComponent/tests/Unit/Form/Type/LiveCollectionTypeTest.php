<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

/**
 * @author Gábor Egyed <gabor.egyed@gmail.com>
 */
final class LiveCollectionTypeTest extends TypeTestCase
{
    public function testAddButtonPrototypeDefaultBlockPrefixes()
    {
        $collectionView = $this->factory->createNamed('fields', LiveCollectionType::class, [], [
            'allow_add' => true,
        ])
            ->createView()
        ;

        $expectedBlockPrefixes = [
            'button',
            'live_collection_button_add',
            '_fields_add',
        ];

        $this->assertCount(0, $collectionView);
        $this->assertSame($expectedBlockPrefixes, $collectionView->vars['button_add']->vars['block_prefixes']);
    }

    public function testAddButtonPrototypeBlockPrefixesWithCustomBlockPrefix()
    {
        $collectionView = $this->factory->createNamed('fields', LiveCollectionType::class, [], [
            'allow_add' => true,
            'button_add_options' => ['block_prefix' => 'custom_prefix'],
        ])
            ->createView()
        ;

        $expectedBlockPrefixes = [
            'button',
            'live_collection_button_add',
            'custom_prefix',
            '_fields_add',
        ];

        $this->assertCount(0, $collectionView);
        $this->assertSame($expectedBlockPrefixes, $collectionView->vars['button_add']->vars['block_prefixes']);
    }

    public function testDeleteButtonPrototypeDefaultBlockPrefixes()
    {
        $collectionView = $this->factory->createNamed('tags', LiveCollectionType::class, [
            'tags' => ['tag01'],
        ], [
            'entry_type' => TextType::class,
            'allow_delete' => true,
        ])
            ->createView()
        ;

        $expectedBlockPrefixes = [
            'button',
            'live_collection_button_delete',
            '_tags_entry_delete',
        ];

        $this->assertCount(1, $collectionView);
        $this->assertSame($expectedBlockPrefixes, $collectionView['tags']->vars['button_delete']->vars['block_prefixes']);
    }

    public function testDeleteButtonPrototypeBlockPrefixesWithCustomBlockPrefix()
    {
        $collectionView = $this->factory->createNamed('tags', LiveCollectionType::class, [
            'tags' => ['tag01'],
        ], [
            'entry_type' => TextType::class,
            'allow_delete' => true,
            'button_delete_options' => ['block_prefix' => 'custom_prefix'],
        ])
            ->createView()
        ;

        $expectedBlockPrefixes = [
            'button',
            'live_collection_button_delete',
            'custom_prefix',
            '_tags_entry_delete',
        ];

        $this->assertCount(1, $collectionView);
        $this->assertSame($expectedBlockPrefixes, $collectionView['tags']->vars['button_delete']->vars['block_prefixes']);
    }
}
