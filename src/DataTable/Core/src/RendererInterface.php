<?php

namespace Symfony\UX\DataTable\Core;

use Twig\Environment;

interface RendererInterface
{
    public function render(Environment $environment, TableInterface $table): string;
}
