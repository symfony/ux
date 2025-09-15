<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-translator')]
final class TranslatorController extends AbstractController
{
    #[Route('/basic')]
    public function basic(): Response
    {
        return $this->render('ux_translator/basic.html.twig');
    }

    #[Route('/with-parameter')]
    public function withParameter(): Response
    {
        return $this->render('ux_translator/with_parameter.html.twig');
    }

    #[Route('/icu-select')]
    public function icuSelect(): Response
    {
        return $this->render('ux_translator/icu_select.html.twig');
    }

    #[Route('/icu-plural')]
    public function icuPlural(): Response
    {
        return $this->render('ux_translator/icu_plural.html.twig');
    }

    #[Route('/icu-selectordinal')]
    public function icuSelectOrdinal(): Response
    {
        return $this->render('ux_translator/icu_selectordinal.html.twig');
    }

    #[Route('/icu-date-time')]
    public function icuDateTime(): Response
    {
        return $this->render('ux_translator/icu_date_time.html.twig');
    }

    #[Route('/icu-number-percent')]
    public function icuNumberPercent(): Response
    {
        return $this->render('ux_translator/icu_number_percent.html.twig');
    }

    #[Route('/icu-number-currency')]
    public function icuNumberCurrency(): Response
    {
        return $this->render('ux_translator/icu_number_currency.html.twig');
    }
}
