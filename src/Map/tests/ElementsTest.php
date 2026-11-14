<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Markers;
use Symfony\UX\Map\Point;

final class ElementsTest extends TestCase
{
    public function testRemoveByIdRemovesTheFirstElementAddedWithThatId(): void
    {
        $first = new Marker(position: new Point(1, 1), title: 'first', id: 'duplicated');
        $second = new Marker(position: new Point(2, 2), title: 'second', id: 'duplicated');
        $markers = new Markers([$first, $second]);

        $markers->remove('duplicated');

        $titles = array_column($markers->toArray(), 'title');

        self::assertSame(['second'], $titles);
    }

    public function testRemoveByIdThenAgainRemovesTheRemainingElement(): void
    {
        $first = new Marker(position: new Point(1, 1), title: 'first', id: 'duplicated');
        $second = new Marker(position: new Point(2, 2), title: 'second', id: 'duplicated');
        $markers = new Markers([$first, $second]);

        $markers->remove('duplicated')->remove('duplicated');

        self::assertSame([], $markers->toArray());
    }

    public function testRemoveByUnknownIdDoesNothing(): void
    {
        $markers = new Markers([new Marker(position: new Point(1, 1), title: 'first', id: 'marker1')]);

        $markers->remove('unknown');

        self::assertCount(1, $markers->toArray());
    }

    public function testRemoveByObjectThenAddAgain(): void
    {
        $marker = new Marker(position: new Point(1, 1), title: 'first', id: 'marker1');
        $other = new Marker(position: new Point(2, 2), title: 'second', id: 'marker2');
        $markers = new Markers([$marker, $other]);

        $markers->remove($marker)->add($marker);

        $titles = array_column($markers->toArray(), 'title');

        self::assertSame(['second', 'first'], $titles);
    }

    public function testRemoveByObjectThenRemoveByItsIdDoesNotRemoveAnotherElement(): void
    {
        $first = new Marker(position: new Point(1, 1), title: 'first', id: 'duplicated');
        $second = new Marker(position: new Point(2, 2), title: 'second', id: 'duplicated');
        $markers = new Markers([$first, $second]);

        $markers->remove($first)->remove('duplicated');

        self::assertSame([], $markers->toArray());
    }

    public function testRemoveAnElementThatWasNeverAdded(): void
    {
        $markers = new Markers([new Marker(position: new Point(1, 1), title: 'first', id: 'marker1')]);

        $markers->remove(new Marker(position: new Point(2, 2), title: 'other', id: 'marker1'));

        $titles = array_column($markers->toArray(), 'title');

        self::assertSame(['first'], $titles);
    }

    public function testRemoveAllThenAddAgain(): void
    {
        $marker = new Marker(position: new Point(1, 1), title: 'first', id: 'marker1');
        $markers = new Markers([$marker]);

        $markers->removeAll()->add($marker)->remove('marker1');

        self::assertSame([], $markers->toArray());
    }

    public function testTheSameElementAddedTwiceIsStoredOnce(): void
    {
        $marker = new Marker(position: new Point(1, 1), title: 'first', id: 'marker1');
        $markers = new Markers([$marker]);

        $markers->add($marker);

        self::assertCount(1, $markers->toArray());

        $markers->remove('marker1');

        self::assertSame([], $markers->toArray());
    }

    public function testElementsWithoutIdAreKept(): void
    {
        $markers = new Markers([
            new Marker(position: new Point(1, 1), title: 'first'),
            new Marker(position: new Point(2, 2), title: 'second'),
        ]);

        $markers->remove('marker1');

        $titles = array_column($markers->toArray(), 'title');

        self::assertSame(['first', 'second'], $titles);
    }
}
