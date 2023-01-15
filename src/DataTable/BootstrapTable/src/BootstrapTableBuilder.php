<?php

namespace Symfony\UX\DataTable\BootstrapTable;

use Symfony\UX\DataTable\Core\AbstractTableBuilder;

class BootstrapTableBuilder extends AbstractTableBuilder
{
    public function getRenderer(): string
    {
        return BootstrapTableRenderer::class;
    }
}
