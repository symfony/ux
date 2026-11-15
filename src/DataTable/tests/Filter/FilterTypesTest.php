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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\UX\DataTable\Filter\BooleanFilterType;
use Symfony\UX\DataTable\Filter\ChoiceFilterType;
use Symfony\UX\DataTable\Filter\DateFilterType;
use Symfony\UX\DataTable\Filter\EnumFilterType;
use Symfony\UX\DataTable\Filter\Filter;
use Symfony\UX\DataTable\Filter\FilterType;
use Symfony\UX\DataTable\Filter\FilterTypeInterface;
use Symfony\UX\DataTable\Filter\NumberFilterType;
use Symfony\UX\DataTable\Filter\SearchFilterType;
use Symfony\UX\DataTable\Filter\TextFilterType;
use Symfony\UX\DataTable\Query\ArrayQuery;
use Symfony\UX\DataTable\Query\Operator;
use Symfony\UX\DataTable\Query\QueryInterface;
use Symfony\UX\DataTable\Tests\Fixtures\Status;
use Symfony\UX\DataTable\TypeRegistry;

final class FilterTypesTest extends TestCase
{
    public function testFilter(): void
    {
        $options = self::options(FilterType::class);

        self::assertSame(TextType::class, $options['form_type']);
        self::assertSame(Operator::Equals, $options['operator']);
        self::assertNull($options['query_path']);
        self::assertNull($options['apply']);
        self::assertSame('filter', new FilterType()->getBlockPrefix());
        self::assertSame('text_filter', new TextFilterType()->getBlockPrefix());
    }

    public function testText(): void
    {
        self::assertSame(Operator::Contains, self::options(TextFilterType::class)['operator']);
    }

    public function testNumber(): void
    {
        self::assertSame(NumberType::class, self::options(NumberFilterType::class)['form_type']);
    }

    public function testDate(): void
    {
        $options = self::options(DateFilterType::class, ['form_options' => ['widget' => 'choice']]);

        self::assertSame(DateType::class, $options['form_type']);
        self::assertEquals(['widget' => 'choice', 'input' => 'datetime_immutable', 'label' => null, 'required' => false], $options['form_options']);
    }

    public function testDateKeepsTheWholeDay(): void
    {
        $rows = [
            ['at' => new \DateTimeImmutable('2026-10-28 23:59:59')],
            ['at' => new \DateTimeImmutable('2026-10-29 00:00:00')],
            ['at' => new \DateTimeImmutable('2026-10-29 14:32:00')],
            ['at' => new \DateTimeImmutable('2026-10-30 00:00:00')],
        ];

        $query = new ArrayQuery($rows);
        new Filter('at', self::options(DateFilterType::class))->apply($query, new \DateTime('2026-10-29 18:00'));
        self::assertSame([$rows[1], $rows[2]], $query->getSource());

        foreach ([[Operator::GreaterThan, [3]], [Operator::GreaterThanOrEqual, [1, 2, 3]], [Operator::LessThan, [0]], [Operator::LessThanOrEqual, [0, 1, 2]]] as [$operator, $expected]) {
            $query = new ArrayQuery($rows);
            new Filter('at', self::options(DateFilterType::class, ['operator' => $operator]))->apply($query, new \DateTimeImmutable('2026-10-29 18:00'));
            self::assertSame(array_map(static fn (int $index): array => $rows[$index], $expected), $query->getSource(), $operator->name);
        }
    }

    public function testDateRejectsOperatorsWithoutDayBounds(): void
    {
        $this->expectException(InvalidOptionsException::class);

        self::options(DateFilterType::class, ['operator' => Operator::NotEquals]);
    }

    public function testChoice(): void
    {
        $options = self::options(ChoiceFilterType::class, ['choices' => ['A' => 'a']]);
        self::assertSame(ChoiceType::class, $options['form_type']);
        self::assertEquals(['choices' => ['A' => 'a'], 'label' => null, 'required' => false], $options['form_options']);
        self::assertSame(Operator::Equals, $options['operator']);

        self::assertSame(Operator::In, self::options(ChoiceFilterType::class, ['form_options' => ['multiple' => true]])['operator']);
        self::assertEquals(['class' => Status::class, 'label' => null, 'required' => false], self::options(ChoiceFilterType::class, ['form_type' => EnumType::class, 'form_options' => ['class' => Status::class]])['form_options']);
    }

    public function testEnum(): void
    {
        $options = self::options(EnumFilterType::class, ['class' => Status::class]);
        self::assertSame(EnumType::class, $options['form_type']);
        self::assertEquals(['class' => Status::class, 'label' => null, 'required' => false], $options['form_options']);
        self::assertSame(Operator::Equals, $options['operator']);

        self::assertSame(Operator::In, self::options(EnumFilterType::class, ['class' => Status::class, 'form_options' => ['multiple' => true]])['operator']);
    }

    public function testBoolean(): void
    {
        $options = self::options(BooleanFilterType::class);

        self::assertSame(ChoiceType::class, $options['form_type']);
        self::assertEquals(['choices' => ['Yes' => true, 'No' => false], 'choice_translation_domain' => 'UXDataTableBundle', 'label' => null, 'required' => false], $options['form_options']);
    }

    public function testSearch(): void
    {
        $options = self::options(SearchFilterType::class, ['fields' => ['name', 'category.name']]);
        self::assertSame(SearchType::class, $options['form_type']);

        $query = $this->createMock(QueryInterface::class);
        $query->expects($this->once())->method('search')->with(['name', 'category.name'], 'chair');
        new Filter('q', $options)->apply($query, 'chair');
    }

    public function testSearchRequiresFields(): void
    {
        $this->expectException(InvalidOptionsException::class);

        self::options(SearchFilterType::class, ['fields' => []]);
    }

    /**
     * @param class-string<FilterTypeInterface> $type
     * @param array<string, mixed>              $options
     *
     * @return array<string, mixed>
     */
    private static function options(string $type, array $options = []): array
    {
        return new TypeRegistry()->resolve($type, FilterTypeInterface::class, $options)[1];
    }
}
