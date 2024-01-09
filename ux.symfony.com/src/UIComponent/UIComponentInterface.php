<?php

namespace App\UIComponent;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag()]
interface UIComponentInterface
{
    public static function getName(): string;

    public static function getKey(): string;

    public function getTemplate(): string;
}
