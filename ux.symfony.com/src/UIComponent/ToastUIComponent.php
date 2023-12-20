<?php

namespace App\UIComponent;

class ToastUIComponent implements UIComponentInterface
{
    public static function getName(): string
    {
        return 'Toast';
    }

    public static function getKey(): string
    {
        return 'toast';
    }

    public function getTemplate(): string
    {
        return 'toast.html.twig';
    }
}
