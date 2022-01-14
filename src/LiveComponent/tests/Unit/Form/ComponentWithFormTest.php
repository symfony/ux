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
use Symfony\UX\LiveComponent\Tests\Fixture\Component\Component6;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class ComponentWithFormTest extends TypeTestCase
{
    public function testFormValues(): void
    {
        $component = new Component6($this->factory->createBuilder());

        $values = $component->getFormValues();
        $this->assertSame(
            $values,
            [
                'text' => '',
                'textarea' => '',
                'range' => '',
                'choice' => '',
                'choice_expanded' => '',
                'checkbox' => null,
                'file' => '',
                'hidden' => '',
            ]
        );
    }
}
