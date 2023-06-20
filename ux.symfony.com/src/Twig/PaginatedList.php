<?php

namespace App\Twig;

use App\Service\DinoPager;
use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent()]
class PaginatedList
{
    public ?FormView $form = null;
    public ?DinoPager $pager = null;
}
