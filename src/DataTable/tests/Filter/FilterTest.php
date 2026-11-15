<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Filter;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Forms;
use Symfony\UX\DataTable\Filter\BooleanFilterType;
use Symfony\UX\DataTable\Filter\Filter;
use Symfony\UX\DataTable\Filter\FilterTypeInterface;
use Symfony\UX\DataTable\Filter\NumberFilterType;
use Symfony\UX\DataTable\Filter\TextFilterType;
use Symfony\UX\DataTable\Query\ArrayQuery;
use Symfony\UX\DataTable\Query\Operator;
use Symfony\UX\DataTable\Query\QueryInterface;
use Symfony\UX\DataTable\TypeRegistry;

final class FilterTest extends TestCase
{
    public function testBuildsItsFormField(): void
    {
        $builder = Forms::createFormFactory()->createBuilder(FormType::class);
        self::filter('price', NumberFilterType::class, ['label' => 'Price', 'form_options' => ['scale' => 2]])->buildForm($builder);

        $field = $builder->getForm()->get('price')->getConfig();
        self::assertInstanceOf(NumberType::class, $field->getType()->getInnerType());
        self::assertSame('Price', $field->getOption('label'));
        self::assertFalse($field->getOption('required'));
        self::assertSame(2, $field->getOption('scale'));
    }

    public function testPassesFormOptionsOverTheFieldDefaults(): void
    {
        $builder = Forms::createFormFactory()->createBuilder(FormType::class);
        self::filter('name', options: ['label' => 'Name', 'form_options' => ['required' => true, 'label' => 'Product name']])->buildForm($builder);

        $field = $builder->getForm()->get('name')->getConfig();
        self::assertTrue($field->getOption('required'));
        self::assertSame('Product name', $field->getOption('label'));
    }

    public function testAppliesTheOperatorAtTheQueryPath(): void
    {
        $query = new ArrayQuery([['name' => 'Chair'], ['name' => 'Table']]);
        self::filter('q', TextFilterType::class, ['query_path' => 'name'])->apply($query, 'tab');

        self::assertSame([['name' => 'Table']], $query->getSource());
    }

    public function testAppliesTheApplyClosure(): void
    {
        $query = new ArrayQuery([['name' => 'Chair'], ['name' => 'Table']]);
        $filter = self::filter('name', TextFilterType::class, [
            'apply' => static fn (QueryInterface $query, string $data, Filter $filter) => $query->filter($filter->getQueryPath(), Operator::Equals, $data.$filter->getOptions()['form_options']['suffix']),
            'form_options' => ['suffix' => 'ir'],
        ]);
        $filter->apply($query, 'Cha');

        self::assertSame([['name' => 'Chair']], $query->getSource());
    }

    #[TestWith([null])]
    #[TestWith([''])]
    #[TestWith([[]])]
    public function testIgnoresEmptyData(mixed $data): void
    {
        $query = $this->createMock(QueryInterface::class);
        $query->expects($this->never())->method('filter');

        self::filter('name')->apply($query, $data);
        self::filter('name')->apply($query, new \ArrayObject());
    }

    public function testIgnoresAnUncheckedCheckbox(): void
    {
        $query = new ArrayQuery([['active' => true], ['active' => false]]);
        $filter = self::filter('active', BooleanFilterType::class, ['form_type' => CheckboxType::class, 'operator' => Operator::Equals]);
        $filter->apply($query, false);

        self::assertCount(2, $query->getSource());

        $filter->apply($query, true);

        self::assertSame([['active' => true]], $query->getSource());
    }

    public function testFiltersOnTheNoChoiceOfABooleanFilter(): void
    {
        $query = new ArrayQuery([['active' => true], ['active' => false]]);
        self::filter('active', BooleanFilterType::class, ['operator' => Operator::Equals])->apply($query, false);

        self::assertSame([['active' => false]], $query->getSource());
    }

    public function testGetters(): void
    {
        $filter = self::filter('name');

        self::assertSame('name', $filter->getName());
        self::assertSame('name', $filter->getQueryPath());
        self::assertSame('product.name', self::filter('name', options: ['query_path' => 'product.name'])->getQueryPath());
        self::assertSame(Operator::Contains, $filter->getOptions()['operator']);
    }

    /**
     * @param class-string<FilterTypeInterface> $type
     * @param array<string, mixed>              $options
     */
    private static function filter(string $name, string $type = TextFilterType::class, array $options = []): Filter
    {
        return new Filter($name, new TypeRegistry()->resolve($type, FilterTypeInterface::class, $options)[1]);
    }
}
