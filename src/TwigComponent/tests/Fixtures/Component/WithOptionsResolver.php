<?php

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;

/**
 * @author Florian Cavasin <cavasinf.info@gmail.com>
 */
#[AsTwigComponent('with_options_resolver')]
final class WithOptionsResolver
{
    public string $prop1;

    public string $prop2 = 'prop2 default value';

    public string $prop3;

    public string $prop4;

    public function mount(bool $arg1 = false): void
    {
        $this->prop3 = 'prop3 mount value ' . ($arg1 ? 'true' : 'false');
    }

    #[PreMount(20)]
    public function preOptionsResolver(array $data): array
    {
        if (isset($data['prop2'])) {
            $data['prop2'] = 'pre-mount ' . $data['prop2'];
        }

        return $data;
    }

    #[PreMount(10)]
    public function optionsResolver(array $data): array
    {
        $resolver = new OptionsResolver();
        $resolver->setIgnoreUndefined();

        $resolver->setDefaults(
            [
                'prop4' => 'prop4 optionsResolver default value',
            ]
        );

        $resolver->setRequired('prop1');
        $resolver->setAllowedTypes('prop1', 'string');
        $resolver->setAllowedValues('prop1', ['allowed 1', 'allowed 2']);

        return $resolver->resolve($data);
    }
}
