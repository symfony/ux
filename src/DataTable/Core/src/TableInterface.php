<?php

namespace Symfony\UX\DataTable\Core;

interface TableInterface
{
    /**
     * ClassName of the Table Renderer.
     */
    public function getRenderer(): string;
}
