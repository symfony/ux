<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;
use Symfony\UX\LiveComponent\Util\LiveFormUtility;

final class LiveFormUtilityTest extends TestCase
{
    /**
     * @dataProvider getPathsTests
     */
    public function testRemovePathsNotInData(array $inputPaths, array $inputData, array $expectedPaths)
    {
        $this->assertEquals($expectedPaths, LiveFormUtility::removePathsNotInData($inputPaths, $inputData));
    }

    public static function getPathsTests(): iterable
    {
        yield 'it_leaves_everything_with_simple_paths' => [
            ['name', 'price'],
            ['name' => null, 'price' => 20],
            ['name', 'price'],
        ];

        yield 'it_leaves_removes_simple_path_not_present' => [
            ['name', 'price'],
            ['price' => 20],
            ['price'],
        ];

        yield 'it_removes_missing_deeper_path' => [
            ['name', 'post.title'],
            ['name' => 'Ryan'],
            ['name'],
        ];

        yield 'it_removes_missing_deeper_indexed_path' => [
            ['name', 'posts.0.title', 'posts.1.title'],
            [
                'name' => 'Ryan',
                'posts' => [
                    1 => ['title' => '1 index post'],
                ],
            ],
            ['name', 'posts.1.title'],
        ];

        yield 'it_removes_changed_deeper_path' => [
            ['name', 'post.title'],
            ['name' => 'Ryan', 'post' => 15],
            ['name'],
        ];
    }

    /**
     * @dataProvider provideFormContainsAnyErrorsTests
     */
    public function testDoesFormContainAnyErrors(FormView $formView, bool $expected)
    {
        $this->assertSame($expected, LiveFormUtility::doesFormContainAnyErrors($formView));
    }

    public static function provideFormContainsAnyErrorsTests()
    {
        yield 'no_errors_key' => [
            new FormView(),
            false,
        ];

        $view = new FormView();
        $view->vars['errors'] = [];
        yield 'error_is_empty' => [
            $view,
            false,
        ];

        $view2 = new FormView();
        $view2->vars['errors'] = ['error'];
        yield 'error_is_not_empty' => [
            $view2,
            true,
        ];

        $view3 = new FormView();
        $view3->children[] = new FormView();
        yield 'error_is_empty_in_children' => [
            $view3,
            false,
        ];

        $view4 = new FormView();
        $childView = new FormView();
        $childView->vars['errors'] = ['error'];
        $view4->children[] = $childView;
        yield 'error_is_not_empty_in_children' => [
            $view4,
            true,
        ];
    }
}
