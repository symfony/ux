<?php

namespace Symfony\UX\DataTables\Builder;

use Symfony\UX\DataTables\Model\DataTable;

interface DataTableBuilderInterface
{
    public function createDataTable(?string $id = null): DataTable;
}
