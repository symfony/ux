<?php

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsTwigComponent('with_exposed_variables')]
final class WithExposedVariables
{
    #[ExposeInTemplate]
    private string $prop1 = 'prop1 value';

    #[ExposeInTemplate('customProp2')]
    private string $prop2 = 'prop2 value';

    #[ExposeInTemplate('customProp3', getter: 'customGetter()')]
    private string $prop3 = 'prop3 value';

    public function getProp1(): string
    {
        return $this->prop1;
    }

    public function getProp2(): string
    {
        return $this->prop2;
    }

    public function customGetter(): string
    {
        return $this->prop3;
    }
}
