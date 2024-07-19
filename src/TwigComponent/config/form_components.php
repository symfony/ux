<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\DependencyInjection\Loader\Configurator;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\TwigComponent\Twig\Form\FormComponent;
use Symfony\UX\TwigComponent\Twig\Form\FormErrorsComponent;
use Symfony\UX\TwigComponent\Twig\Form\FormHelpComponent;
use Symfony\UX\TwigComponent\Twig\Form\FormLabelComponent;
use Symfony\UX\TwigComponent\Twig\Form\FormRowComponent;
use Symfony\UX\TwigComponent\Twig\Form\FormWidgetComponent;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('ux.twig_component.form_component', FormComponent::class)
            ->tag('twig.component', [
                'key' => 'Form',
                'template' => '@TwigComponent/Component/Form.html.twig',
                'expose_public_props' => true,
                'attributes_var' => 'attributes',
            ])

        ->set('ux.twig_component.form_row_component', FormRowComponent::class)
            ->tag('twig.component', [
                'key' => 'Form:Row',
                'template' => '@TwigComponent/Component/FormRow.html.twig',
                'expose_public_props' => true,
                'attributes_var' => 'attributes',
            ])

        ->set('ux.twig_component.form_label_component', FormLabelComponent::class)
            ->tag('twig.component', [
                'key' => 'Form:Label',
                'template' => '@TwigComponent/Component/FormLabel.html.twig',
                'expose_public_props' => true,
                'attributes_var' => 'attributes',
            ])

        ->set('ux.twig_component.form_widget_component', FormWidgetComponent::class)
            ->tag('twig.component', [
                'key' => 'Form:Widget',
                'template' => '@TwigComponent/Component/FormWidget.html.twig',
                'expose_public_props' => true,
                'attributes_var' => 'attributes',
            ])

        ->set('ux.twig_component.form_help_component', FormHelpComponent::class)
            ->tag('twig.component', [
                'key' => 'Form:Help',
                'template' => '@TwigComponent/Component/FormHelp.html.twig',
                'expose_public_props' => true,
                'attributes_var' => 'attributes',
            ])

        ->set('ux.twig_component.form_errors_component', FormErrorsComponent::class)
            ->tag('twig.component', [
                'key' => 'Form:Errors',
                'template' => '@TwigComponent/Component/FormErrors.html.twig',
                'expose_public_props' => true,
                'attributes_var' => 'attributes',
            ])
        ;
};
