<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\UX\LiveComponent\Tests\Unit\Form;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\FormComponent1;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class ComponentWithFormTest extends KernelTestCase
{
    public function testFormValues(): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        $component = new FormComponent1($formFactory);
        $component->postMount([]);

        $this->assertSame(
            [
                'text' => '',
                'textarea' => '',
                'range' => '',
                'choice' => '',
                'choice_expanded' => '',
                'choice_multiple' => ['2'],
                'select_multiple' => ['2'],
                'checkbox' => null,
                'checkbox_checked' => '1',
                'file' => '',
                'hidden' => '',
            ],
            $component->formValues
        );
    }
}
