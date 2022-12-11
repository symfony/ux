<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\BootstrapTable\Twig;

use Symfony\UX\BootstrapTable\Model\Table;
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
class BootstrapTableExtension extends AbstractExtension
{
    private $stimulus;

    public function __construct(StimulusTwigExtension $stimulus)
    {
        $this->stimulus = $stimulus;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_table', [$this, 'renderTable'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function renderTable(Environment $environment, Table $table): string
    {
        $rendered = '<table data-toggle="table" ';
        $rendered .= $this->renderTableAttributes($table->getTableAttributes()).'>';
        $rendered .= $this->renderTableHeader($table);
        $rendered .= $this->renderTableBody($table->getColumns(), $table->getData());
        $rendered .= '</table>';

        return $rendered;
    }

    private function renderTableHeader(Table $table): string
    {
        $renderedCols = '';
        foreach ($table->getColumns() as $col) {
            $renderedCols .= '<th '.$this->renderColumnsAttributes($col, $table->getColumnsAttributes()).'>'.$col.'</th>';
        }

        return '<thead><tr>'.$renderedCols.'</tr></thead>';
    }

    private function renderColumnsAttributes(string $column, array $columnsAttributes): string
    {
        $results = '';

        if (!isset($columnsAttributes[$column])) {
            return '';
        }

        foreach ($columnsAttributes[$column] as $key => $value) {
            $results .= $key.'="'.$value.'" ';
        }

        return $results;
    }

    private function renderTableBody(array $cols, array $data): string
    {
        $content = '';
        foreach ($data as $row) {
            $renderedRow = '<tr>';
            foreach ($cols as $col) {
                $renderedRow .= '<td>'.$row[$col].'</td>';
            }
            $content .= $renderedRow.'</tr>';
        }

        return '<tbody>'.$content.'</tbody>';
    }

    private function renderTableAttributes(array $attributes): string
    {
        $result = '';
        foreach ($attributes as $key => $value) {
            $result .= $key.'="'.$value.'" ';
        }

        return $result;
    }
}
