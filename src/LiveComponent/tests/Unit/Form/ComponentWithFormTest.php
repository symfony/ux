<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Form;

use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\UX\LiveComponent\Tests\Fixture\Component\FormComponent1;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class ComponentWithFormTest extends TypeTestCase
{
    public function testFormValues(): void
    {
        $component = new FormComponent1($this->factory->createBuilder());

        $this->assertSame(
            [
                'text' => '',
                'textarea' => '',
                'range' => '',
                'choice' => '',
                'choice_expanded' => '',
                'checkbox' => null,
                'file' => '',
                'hidden' => '',
            ],
            $component->getFormValues()
        );
    }
}
