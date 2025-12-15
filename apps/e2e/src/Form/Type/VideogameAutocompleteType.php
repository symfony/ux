<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class VideogameAutocompleteType extends AbstractType
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete' => true,
            'autocomplete_url' => $this->urlGenerator->generate('app_test_autocomplete_videogame'),
            'tom_select_options' => [
                'maxOptions' => null,
            ],
            'attr' => [
                'data-test-id' => 'videogame-autocomplete',
                'data-controller' => 'videogame-autocomplete',
            ],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
