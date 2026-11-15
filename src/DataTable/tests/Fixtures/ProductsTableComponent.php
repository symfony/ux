<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Fixtures;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\UX\DataTable\DataTable;
use Symfony\UX\DataTable\DataTableFactory;
use Symfony\UX\DataTable\Filter\FilterType;
use Symfony\UX\DataTable\LiveComponent\ComponentWithDataTableTrait;
use Symfony\UX\DataTable\Query\Operator;
use Symfony\UX\DataTable\Query\QueryInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('products_table', template: 'components/products_table.html.twig')]
final class ProductsTableComponent
{
    use ComponentWithDataTableTrait;
    use DefaultActionTrait;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $options = [];

    #[LiveProp]
    public bool $filterable = true;

    #[LiveProp]
    public bool $cheapByDefault = false;

    public function __construct(
        private readonly DataTableFactory $dataTableFactory,
    ) {
    }

    protected function createDataTable(): DataTable
    {
        $builder = $this->dataTableFactory->createBuilder(ProductDataTableType::class, Products::all(), ['per_page' => 2, ...$this->options]);
        if (!$this->filterable) {
            return $builder->removeFilter('q')->removeFilter('active')->getDataTable();
        }

        return $builder
            ->addFilter('cheap', FilterType::class, [
                'form_type' => CheckboxType::class,
                'form_options' => ['data' => $this->cheapByDefault],
                'apply' => static function (QueryInterface $query, bool $cheap): void {
                    if ($cheap) {
                        $query->filter('price', Operator::LessThan, 25);
                    }
                },
            ])
            ->getDataTable();
    }
}
