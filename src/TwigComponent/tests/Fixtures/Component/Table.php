<?php

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('table')]
final class Table
{
    public ?string $caption = null;
    public array $headers = ['key', 'value'];
    public array $data = [[1, 2], [3, 4]];
}
