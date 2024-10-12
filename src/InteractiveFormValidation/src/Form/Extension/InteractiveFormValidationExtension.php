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

namespace Symfony\UX\InteractiveFormValidation\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\InteractiveFormValidation\Alert\Strategy as AlertStrategy;

/**
 * @author Mateusz Anders <anders_mateusz@outlook.com>
*/
final class InteractiveFormValidationExtension extends AbstractTypeExtension
{
    public static string $enabled = 'interactive_form_validation_enabled';
    public static string $withAlert = 'interactive_form_validation_with_alert';
    public static string $alertStrategy = 'interactive_form_validation_alert_strategy';
    private static string $sessionVarName = 'ux-interactive-form-validation-passed-args';
    private static string $stimulusControllerName = 'symfony--ux-interactive-form-validation--interactive-form-validation';

    /** @param array{ with_alert: bool, alert_strategy: AlertStrategy, enabled: bool } $config */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly FormRendererInterface $formRenderer,
        private readonly array $config,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$options[self::$enabled]
            || !is_array($sessionVar = $this->requestStack->getSession()->get(self::$sessionVarName))
        ) {
            return;
        }
        $validationMsg = $sessionVar['validationMsg'] ?? null;
        $targetId = $sessionVar['id'] ?? null;
        $destinationId = $view->vars['id'] ?? null;

        if (!$targetId || !$destinationId || $targetId !== $destinationId) {
            return;
        }

        $label = $this->formRenderer->renderBlock($view, 'form_label_content');
        $msg = $label && !$form->isRoot() ? "$label: $validationMsg" : $validationMsg;
        $stimulusName = self::$stimulusControllerName;
        $view->vars['attr'] = array_merge_recursive(
            $view->vars['attr'] ?? [],
            array_filter([
                'data-controller' => "$stimulusName " . ($view->vars['attr']['data-controller'] ?? ''),
                "data-$stimulusName-msg-value" => $msg,
                "data-$stimulusName-id-value" => $targetId,
                "data-$stimulusName-with-alert-value" => (string) $options[self::$withAlert],
                "data-$stimulusName-alert-strategy-value" => $options[self::$alertStrategy]->value,
            ]),
        );

        $this->requestStack->getSession()->remove(self::$sessionVarName);
    }

    public function buildForm(FormBuilderInterface $builder,array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $ev): void {
            $form = $ev->getForm();
            if (!$form->getConfig()->getOption(self::$enabled, false)) {
                return;
            }

            if ($form->isRoot() && !$form->isValid()) {
                /** @var FormError|null $err */
                if ($err = iterator_to_array($form->getErrors(true))[0] ?? false) {
                    $this->requestStack->getSession()->set(self::$sessionVarName, [
                        'validationMsg' => $err->getMessage(),
                        'id' => $err->getOrigin()?->createView()->vars['id'] ?? null,
                    ]);
                }
            }
        }, -1);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                self::$enabled => $this->config['enabled'],
                self::$withAlert => $this->config['with_alert'],
                self::$alertStrategy => $this->config['alert_strategy'],
            ])
            ->setAllowedTypes(self::$enabled, 'bool')
            ->setAllowedTypes(self::$withAlert, 'bool')
            ->setAllowedTypes(self::$alertStrategy, AlertStrategy::class)
        ;
    }
}