<?php

namespace App\Twig;

use App\Service\DinoPager;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent()]
class PaginatedList
{
    public ?FormView $form = null;
    public ?DinoPager $pager = null;

    #[PreMount]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('form', null);
        $resolver->setRequired('pager');
        $resolver->setAllowedTypes('form', ['null', FormView::class]);
        $resolver->setAllowedTypes('pager', DinoPager::class);

        return $resolver->resolve($data);
    }
}
