<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Filter;

use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\DataTable\Query\Operator;
use Symfony\UX\DataTable\Query\QueryInterface;

/**
 * Keeps the rows of the chosen day, or of the days before or after it with
 * another "operator", whatever their time.
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class DateFilterType extends AbstractFilterType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'form_type' => DateType::class,
                'apply' => static function (QueryInterface $query, \DateTimeInterface $date, Filter $filter): void {
                    $day = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0);
                    $nextDay = $day->modify('+1 day');

                    // Compares to the midnights around the day, as datetime values of the day are not equal to its midnight
                    $bounds = match ($filter->getOptions()['operator']) {
                        Operator::Equals => [[Operator::GreaterThanOrEqual, $day], [Operator::LessThan, $nextDay]],
                        Operator::GreaterThan => [[Operator::GreaterThanOrEqual, $nextDay]],
                        Operator::GreaterThanOrEqual => [[Operator::GreaterThanOrEqual, $day]],
                        Operator::LessThan => [[Operator::LessThan, $day]],
                        // LessThanOrEqual, the allowed values of the operator being checked by the resolver
                        default => [[Operator::LessThan, $nextDay]],
                    };
                    foreach ($bounds as [$operator, $bound]) {
                        $query->filter($filter->getQueryPath(), $operator, $bound);
                    }
                },
            ])
            ->setAllowedValues('operator', [Operator::Equals, Operator::GreaterThan, Operator::GreaterThanOrEqual, Operator::LessThan, Operator::LessThanOrEqual])
            ->addNormalizer('form_options', static fn (Options $options, array $formOptions): array => $formOptions + [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
        ;
    }
}
