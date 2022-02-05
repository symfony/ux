<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Tests\Fixtures\Form\FormWithManyDifferentFieldsType;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
#[AsLiveComponent('form_with_many_different_fields_type')]
class FormComponentWithManyDifferentFieldsType extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    public function __construct(private FormFactoryInterface $formFactory)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->createNamed('form', FormWithManyDifferentFieldsType::class);
    }
}
