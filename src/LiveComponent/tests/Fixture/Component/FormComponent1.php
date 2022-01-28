<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixture\Component;

use Symfony\UX\LiveComponent\Tests\Fixture\Form\FormType1;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
#[AsLiveComponent('form_component1')]
class FormComponent1 extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    public function __construct(private FormFactoryInterface $formFactory)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->createNamed('form', FormType1::class);
    }
}
