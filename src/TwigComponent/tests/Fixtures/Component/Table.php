<?php

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('table')]
final class Table
{
    public ?string $caption = null;
    public array $headers;
    public array $data;
}
