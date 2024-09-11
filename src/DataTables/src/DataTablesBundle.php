<?php

namespace Symfony\UX\DataTables;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class DataTablesBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
