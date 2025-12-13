<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-translator', name: 'app_ux_translator_')]
final class TranslatorController extends AbstractController
{
    #[Route('/basic', name: 'basic')]
    public function basic(): Response
    {
        return $this->render('ux_translator/basic.html.twig');
    }

    #[Route('/with-parameter', name: 'with_parameter')]
    public function withParameter(): Response
    {
        return $this->render('ux_translator/with_parameter.html.twig');
    }

    #[Route('/icu-select', name: 'icu_select')]
    public function icuSelect(): Response
    {
        return $this->render('ux_translator/icu_select.html.twig');
    }

    #[Route('/icu-plural', name: 'icu_plural')]
    public function icuPlural(): Response
    {
        return $this->render('ux_translator/icu_plural.html.twig');
    }

    #[Route('/icu-selectordinal', name: 'icu_selectordinal')]
    public function icuSelectOrdinal(): Response
    {
        return $this->render('ux_translator/icu_selectordinal.html.twig');
    }

    #[Route('/icu-date-time', name: 'icu_date_time')]
    public function icuDateTime(): Response
    {
        return $this->render('ux_translator/icu_date_time.html.twig');
    }

    #[Route('/icu-number-percent', name: 'icu_number_percent')]
    public function icuNumberPercent(): Response
    {
        return $this->render('ux_translator/icu_number_percent.html.twig');
    }

    #[Route('/icu-number-currency', name: 'icu_number_currency')]
    public function icuNumberCurrency(): Response
    {
        return $this->render('ux_translator/icu_number_currency.html.twig');
    }
}
