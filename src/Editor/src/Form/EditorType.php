<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Editor\Bridge\BridgeRegistry;
use Symfony\UX\Editor\Config\AbstractEditorConfig;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Config\Preset\PresetRegistry;
use Symfony\UX\Editor\DataCollector\UXEditorDataCollector;
use Symfony\UX\Editor\Exception\BridgeConfigMismatchException;
use Symfony\UX\Editor\Form\DataTransformer\TransformerAdapter;

class EditorType extends AbstractType
{
    public function __construct(
        private readonly BridgeRegistry $bridges,
        private readonly PresetRegistry $presets,
        private readonly ?HtmlSanitizerInterface $sanitizer = null,
        private readonly ?UXEditorDataCollector $collector = null,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'config' => null,
            'preset' => null,
            'bridge' => null,
            'common' => null,
            'native' => null,
            'sanitize' => true,
            'strictCapabilities' => false,
            'upload_url' => null,
            'compound' => false,
        ]);
        $resolver->setAllowedTypes('config', ['null', EditorConfigInterface::class]);
        $resolver->setAllowedTypes('preset', ['null', 'string']);
        $resolver->setAllowedTypes('bridge', ['null', 'string']);
        $resolver->setAllowedTypes('common', ['null', 'array']);
        $resolver->setAllowedTypes('native', ['null', 'array']);
        $resolver->setAllowedTypes('sanitize', 'bool');
        $resolver->setAllowedTypes('strictCapabilities', 'bool');
        $resolver->setAllowedTypes('upload_url', ['null', 'string']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $config = $this->resolveConfig($options);
        $bridge = $this->bridges->get($config->getBridgeId());

        if ($config instanceof AbstractEditorConfig && $options['strictCapabilities']) {
            $config->setStrict(true);
        }

        $builder->addModelTransformer(new TransformerAdapter($bridge->createTransformer()));
        $builder->setAttribute('ux_editor_config', $config);
        $builder->setAttribute('ux_editor_bridge', $bridge);

        if ($options['sanitize'] && null !== $this->sanitizer) {
            $sanitizer = $this->sanitizer;
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($sanitizer): void {
                $data = $event->getData();
                if (\is_string($data)) {
                    $event->setData($sanitizer->sanitize($data));
                }
            });
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        /** @var \Symfony\UX\Editor\Bridge\BridgeInterface $bridge */
        $bridge = $form->getConfig()->getAttribute('ux_editor_bridge');
        /** @var EditorConfigInterface $config */
        $config = $form->getConfig()->getAttribute('ux_editor_config');

        $controller = $bridge->getControllerName();
        $format = $config->getCapabilities()->supportedFormats[0] ?? 'html';

        $wrapperAttr = [
            'data-controller' => $controller,
            "data-{$controller}-config-value" => json_encode($config->toNative(), \JSON_THROW_ON_ERROR),
            "data-{$controller}-format-value" => $format,
            "data-{$controller}-bridge-id-value" => $config->getBridgeId(),
        ];
        if (null !== $options['upload_url']) {
            $wrapperAttr["data-{$controller}-upload-url-value"] = $options['upload_url'];
        }

        $view->vars['ux_editor'] = [
            'controller' => $controller,
            'wrapper_attr' => $wrapperAttr,
            'input_target_attr' => 'data-'.$controller.'-target',
        ];

        // Keep textarea attrs clean — controller attributes live on the wrapper rendered by the form theme.
        // Add input target on the textarea so AbstractEditorController.inputTarget resolves.
        $textareaAttr = $view->vars['attr'] ?? [];
        $textareaAttr['data-'.$controller.'-target'] = 'input';
        $view->vars['attr'] = $textareaAttr;

        $this->collector?->recordBridgeUse($config->getBridgeId(), $format);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'ux_editor';
    }

    private function resolveConfig(array $options): EditorConfigInterface
    {
        if ($options['config'] instanceof EditorConfigInterface) {
            return $options['config'];
        }
        if (\is_string($options['preset'])) {
            return $this->presets->get($options['preset'])->build();
        }
        if (\is_string($options['bridge'])) {
            $bridge = $this->bridges->get($options['bridge']);
            $config = $bridge->getDefaultConfig();
            if (!$config instanceof AbstractEditorConfig) {
                throw new BridgeConfigMismatchException(sprintf('Bridge "%s" default config must extend AbstractEditorConfig to accept array shorthand.', $options['bridge']));
            }
            $common = null !== $options['common'] ? CommonOptions::fromArray($options['common']) : $config->getCommon();
            $native = $options['native'] ?? $config->getNativeOverrides();
            $r = new \ReflectionObject($config);
            $r->getProperty('common')->setValue($config, $common);
            $r->getProperty('nativeOverrides')->setValue($config, $native);

            return $config;
        }
        throw new \InvalidArgumentException('EditorType requires one of: "config", "preset", or "bridge" option.');
    }
}
