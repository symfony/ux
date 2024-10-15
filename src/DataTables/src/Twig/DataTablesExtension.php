<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTables\Twig;

use Symfony\UX\DataTables\Model\DataTable;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DataTablesExtension extends AbstractExtension
{
    public function __construct(
        private StimulusHelper $stimulus,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_datatable', [$this, 'renderDataTable'], ['is_safe' => ['html']]),
        ];
    }

    public function renderDataTable(DataTable $table, array $attributes = []): string
    {
        $table->setAttributes(array_merge($table->getAttributes(), $attributes));

        $controllers = [];
        if ($table->getDataController()) {
            $controllers[$table->getDataController()] = [];
        }
        $controllers['@symfony/ux-datatables/datatable'] = ['view' => $table->getOptions()];

        $stimulusAttributes = $this->stimulus->createStimulusAttributes();
        foreach ($controllers as $name => $controllerValues) {
            $stimulusAttributes->addController($name, $controllerValues);
        }

        foreach ($table->getAttributes() as $name => $value) {
            if ('data-controller' === $name) {
                continue;
            }

            if (true === $value) {
                $stimulusAttributes->addAttribute($name, $name);
            } elseif (false !== $value) {
                $stimulusAttributes->addAttribute($name, $value);
            }
        }

        return \sprintf('<table id="%s" %s></table>', $table->getId(), $stimulusAttributes);
    }
}
