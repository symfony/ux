<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\BootstrapTable;

use Symfony\UX\DataTable\Core\RendererInterface;
use Symfony\UX\DataTable\Core\TableInterface;
use Symfony\WebpackEncoreBundle\Dto\StimulusControllersDto;
use Twig\Environment;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
class BootstrapTableRenderer implements RendererInterface
{
    public function render(Environment $environment, TableInterface $table): string
    {
        $dto = new StimulusControllersDto($environment);
        $dto->addController('@symfony/ux-bootstrap-table/table', $table->renderView());

        return '<table '.$dto.'></table>';
    }
}
