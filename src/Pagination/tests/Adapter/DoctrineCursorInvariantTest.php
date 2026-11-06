<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\Adapter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Adapter\DoctrineOrmAdapter;
use Symfony\UX\Pagination\Cursor\CursorBoundary;
use Symfony\UX\Pagination\Cursor\CursorCodec;
use Symfony\UX\Pagination\Cursor\CursorOrder;
use Symfony\UX\Pagination\Exception\InvalidCursorException;
use Symfony\UX\Pagination\Paginator;
use Symfony\UX\Pagination\Tests\Fixtures\Entity\Author;
use Symfony\UX\Pagination\Tests\Fixtures\Entity\Book;
use Symfony\UX\Pagination\Tests\Fixtures\Entity\Category;
use Symfony\UX\Pagination\Tests\Fixtures\EntityManagerFactory;

/**
 * Invariant tests for cursor pagination on the Doctrine ORM adapter.
 *
 * Walking all pages must partition the dataset (no duplicate, no gap),
 * backward navigation must be the exact inverse of forward navigation,
 * and insert/delete mutations that do not change surviving order values must
 * not reproduce the offset-shift skips or duplicates.
 */
#[CoversClass(DoctrineOrmAdapter::class)]
final class DoctrineCursorInvariantTest extends TestCase
{
    private EntityManager $entityManager;
    private DoctrineOrmAdapter $adapter;
    private CursorOrder $ascendingIdOrder;

    protected function setUp(): void
    {
        if (!class_exists(EntityManager::class)) {
            self::markTestSkipped('Doctrine ORM is not installed.');
        }

        $this->entityManager = EntityManagerFactory::create();

        $this->adapter = new DoctrineOrmAdapter();
        $this->ascendingIdOrder = CursorOrder::byFields(['id'], 'ASC');
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $this->entityManager->close();
        }
    }

    public function testForwardWalkPartitionsDataset(): void
    {
        $this->createAuthors(23);

        $ids = [];
        $cursor = null;
        $guard = 0;
        do {
            $page = $this->adapter->sliceWithCursor($this->queryBuilder(), $cursor, 5, $this->ascendingIdOrder);
            $ids = [...$ids, ...$this->idsOf($page->items)];
            $cursor = $page->next;
        } while (null !== $cursor && ++$guard < 50);

        self::assertSame(range(1, 23), $ids);
    }

    public function testBackwardWalkIsExactInverseOfForwardWalk(): void
    {
        $this->createAuthors(23);

        $forwardPages = [];
        $cursor = null;
        $guard = 0;
        do {
            $page = $this->adapter->sliceWithCursor($this->queryBuilder(), $cursor, 5, $this->ascendingIdOrder);
            $forwardPages[] = $this->idsOf($page->items);
            $cursor = $page->next;
            $lastPage = $page;
        } while (null !== $cursor && ++$guard < 50);

        self::assertCount(5, $forwardPages);

        $backwardPages = [];
        $cursor = $lastPage->previous;
        $guard = 0;
        while (null !== $cursor && ++$guard < 50) {
            $page = $this->adapter->sliceWithCursor($this->queryBuilder(), $cursor, 5, $this->ascendingIdOrder);
            $backwardPages[] = $this->idsOf($page->items);
            $cursor = $page->previous;
        }

        self::assertSame(\array_slice($forwardPages, 0, -1), array_reverse($backwardPages));
    }

    public function testDeletionBetweenPagesNeverSkipsSurvivors(): void
    {
        $this->createAuthors(15);

        $page1 = $this->adapter->sliceWithCursor($this->queryBuilder(), null, 5, $this->ascendingIdOrder);
        self::assertSame(range(1, 5), $this->idsOf($page1->items));

        // Delete one already-seen row and one upcoming row
        foreach ([3, 7] as $id) {
            $author = $this->entityManager->find(Author::class, $id);
            self::assertNotNull($author);
            $this->entityManager->remove($author);
        }
        $this->entityManager->flush();

        $page2 = $this->adapter->sliceWithCursor($this->queryBuilder(), $page1->next, 5, $this->ascendingIdOrder);

        // Offset pagination would skip id 6 here; cursors must not
        self::assertSame([6, 8, 9, 10, 11], $this->idsOf($page2->items));
    }

    public function testInsertionBetweenPagesNeverDuplicates(): void
    {
        $this->createAuthors(15);

        $page1 = $this->adapter->sliceWithCursor($this->queryBuilder(), null, 5, $this->ascendingIdOrder);

        // New row inserted mid-walk (gets id 16, after the cursor position)
        $author = new Author();
        $author->setName('Inserted');
        $this->entityManager->persist($author);
        $this->entityManager->flush();

        $seen = $this->idsOf($page1->items);
        $cursor = $page1->next;
        $guard = 0;
        while (null !== $cursor && ++$guard < 50) {
            $page = $this->adapter->sliceWithCursor($this->queryBuilder(), $cursor, 5, $this->ascendingIdOrder);
            $seen = [...$seen, ...$this->idsOf($page->items)];
            $cursor = $page->next;
        }

        self::assertSame($seen, array_unique($seen), 'No row may appear twice across the walk');
        self::assertSame(range(1, 16), $seen, 'Every row, including the inserted one, is seen exactly once');
    }

    #[DataProvider('provideDateCursorFields')]
    public function testDateCursorBoundariesAreStableInNonUtcDefaultTimezone(string $field): void
    {
        $previousTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');

        try {
            $schemaTool = new SchemaTool($this->entityManager);
            $schemaTool->createSchema([
                $this->entityManager->getClassMetadata(Book::class),
                $this->entityManager->getClassMetadata(Category::class),
            ]);

            // Three books per day for the date fields, so that page boundaries fall inside a day
            for ($i = 1; $i <= 23; ++$i) {
                $book = new Book();
                $book->setTitle('Book '.$i);
                $book->setPublishedAt(new \DateTimeImmutable(\sprintf('2024-06-01 12:%02d:00', $i)));
                $day = \sprintf('2024-06-%02d', intdiv($i - 1, 3) + 1);
                $book->setReleasedOn(new \DateTimeImmutable($day));
                $book->setPrintedOn(new \DateTime($day));
                $this->entityManager->persist($book);
            }
            $this->entityManager->flush();
            $this->entityManager->clear();

            $order = CursorOrder::byFields([$field, 'id'], 'ASC');

            $ids = [];
            $cursor = null;
            $guard = 0;
            do {
                $page = $this->adapter->sliceWithCursor($this->bookQueryBuilder(), $cursor, 5, $order);
                $ids = [...$ids, ...array_map(static fn (Book $book) => $book->getId(), $page->items)];
                $cursor = $page->next;
            } while (null !== $cursor && ++$guard < 50);

            self::assertSame(range(1, 23), $ids);
        } finally {
            date_default_timezone_set($previousTimezone);
        }
    }

    public static function provideDateCursorFields(): iterable
    {
        yield 'datetime_immutable' => ['publishedAt'];
        yield 'date_immutable' => ['releasedOn'];
        yield 'date' => ['printedOn'];
    }

    /**
     * @param 'ASC'|'DESC' $direction
     */
    #[DataProvider('provideDirections')]
    public function testNullableFieldWalkMatchesTheDatabaseOrder(string $direction): void
    {
        $this->createBooksWithNullableRatings(23);
        $order = CursorOrder::byFields(['rating', 'id'], $direction);

        // NULLs sort where the platform puts them; the walk must follow the same order
        $expected = array_map(
            static fn (Book $book) => $book->getId(),
            $this->bookQueryBuilder()->orderBy('b.rating', $direction)->addOrderBy('b.id', $direction)->getQuery()->getResult(),
        );

        $forwardPages = [];
        $cursor = null;
        $guard = 0;
        do {
            $page = $this->adapter->sliceWithCursor($this->bookQueryBuilder(), $cursor, 5, $order);
            $forwardPages[] = array_map(static fn (Book $book) => $book->getId(), $page->items);
            $cursor = $page->next;
            $lastPage = $page;
        } while (null !== $cursor && ++$guard < 50);

        self::assertSame($expected, array_merge(...$forwardPages));

        $backwardPages = [];
        $cursor = $lastPage->previous;
        $guard = 0;
        while (null !== $cursor && ++$guard < 50) {
            $page = $this->adapter->sliceWithCursor($this->bookQueryBuilder(), $cursor, 5, $order);
            $backwardPages[] = array_map(static fn (Book $book) => $book->getId(), $page->items);
            $cursor = $page->previous;
        }

        self::assertSame(\array_slice($forwardPages, 0, -1), array_reverse($backwardPages));
    }

    public static function provideDirections(): iterable
    {
        yield 'ascending' => ['ASC'];
        yield 'descending' => ['DESC'];
    }

    public function testNullableFieldBoundaryRoundTripsThroughTheCodec(): void
    {
        $this->createBooksWithNullableRatings(6);
        $order = CursorOrder::byFields(['rating', 'id'], 'ASC');
        $codec = new CursorCodec('secret');

        // Every boundary of a 1-row walk, NULL ones included, survives encoding
        $ids = [];
        $cursor = null;
        $guard = 0;
        do {
            $page = $this->adapter->sliceWithCursor($this->bookQueryBuilder(), $cursor, 1, $order);
            $ids = [...$ids, ...array_map(static fn (Book $book) => $book->getId(), $page->items)];
            $cursor = null;
            if (null !== $page->next) {
                $decoded = $codec->decode($codec->encode($page->next->values, true, 'order', 'context'), 'order', 'context');
                $cursor = new CursorBoundary($decoded['values'], $decoded['forward']);
            }
        } while (null !== $cursor && ++$guard < 50);

        sort($ids);
        self::assertSame(range(1, 6), $ids);
    }

    /**
     * @param 'ASC'|'DESC' $direction
     */
    #[DataProvider('provideDirections')]
    public function testAssociationFieldWalkMatchesTheDatabaseOrder(string $direction): void
    {
        $this->createBooksWithAuthors(23);
        $order = CursorOrder::byFields(['author.name', 'id'], $direction);

        $expected = array_map(
            static fn (Book $book) => $book->getId(),
            $this->bookQueryBuilder()->leftJoin('b.author', 'a')->orderBy('a.name', $direction)->addOrderBy('b.id', $direction)->getQuery()->getResult(),
        );

        $forwardPages = [];
        $cursor = null;
        $guard = 0;
        do {
            $page = $this->adapter->sliceWithCursor($this->bookQueryBuilder(), $cursor, 5, $order);
            $forwardPages[] = array_map(static fn (Book $book) => $book->getId(), $page->items);
            $cursor = $page->next;
            $lastPage = $page;
        } while (null !== $cursor && ++$guard < 50);

        self::assertSame($expected, array_merge(...$forwardPages));

        $backwardPages = [];
        $cursor = $lastPage->previous;
        $guard = 0;
        while (null !== $cursor && ++$guard < 50) {
            $page = $this->adapter->sliceWithCursor($this->bookQueryBuilder(), $cursor, 5, $order);
            $backwardPages[] = array_map(static fn (Book $book) => $book->getId(), $page->items);
            $cursor = $page->previous;
        }

        self::assertSame(\array_slice($forwardPages, 0, -1), array_reverse($backwardPages));
    }

    public function testExistingOrderIsRejectedEvenWhenCompatible(): void
    {
        $this->createAuthors(8);
        $query = $this->queryBuilder()->orderBy('a.id', 'ASC');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cursor pagination owns ORDER BY');
        $this->adapter->sliceWithCursor($query, null, 5, $this->ascendingIdOrder);
    }

    public function testIncompatibleExistingOrderIsRejected(): void
    {
        $this->createAuthors(8);
        $query = $this->queryBuilder()->orderBy('a.name', 'ASC');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cursor pagination owns ORDER BY');
        $this->adapter->sliceWithCursor($query, null, 5, $this->ascendingIdOrder);
    }

    public function testAutomaticContextBindsDoctrineDqlAndParameters(): void
    {
        $this->createAuthors(12);
        $paginator = new Paginator([$this->adapter], cursorCodec: new CursorCodec('test-secret'));
        $firstQuery = $this->queryBuilder()
            ->andWhere('a.name LIKE :pattern')
            ->setParameter('pattern', 'Author %');
        $token = $paginator->cursor($firstQuery)
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->paginate()
            ->getNextCursor();
        self::assertNotNull($token);

        $otherQuery = $this->queryBuilder()
            ->andWhere('a.name LIKE :pattern')
            ->setParameter('pattern', 'Other %');
        $builder = $paginator->cursor($otherQuery)
            ->orderBy('id', 'ASC')
            ->perPage(5)
            ->cursor($token);

        $this->expectException(InvalidCursorException::class);
        $builder->paginate();
    }

    public function testSignedOrderIncludesAutomaticallyAppendedIdentifierFields(): void
    {
        $this->createAuthors(6);
        $codec = new CursorCodec('test-secret');
        $paginator = new Paginator([$this->adapter], cursorCodec: $codec);
        $query = $this->queryBuilder();

        $token = $paginator->cursor($query)
            ->orderBy('name', 'ASC')
            ->perPage(2)
            ->paginate()
            ->getNextCursor();

        self::assertNotNull($token);
        $effectiveOrder = $this->adapter
            ->resolveCursorOrder($query, ['name'], 'ASC')
            ->getFingerprint();
        $decoded = $codec->decode(
            $token,
            $effectiveOrder,
            $this->adapter->getCursorContext($query, null),
        );

        self::assertCount(2, $decoded['values']);
    }

    private function createAuthors(int $count): void
    {
        for ($i = 1; $i <= $count; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();
    }

    private function createBooksWithNullableRatings(int $count): void
    {
        new SchemaTool($this->entityManager)->createSchema([
            $this->entityManager->getClassMetadata(Book::class),
            $this->entityManager->getClassMetadata(Category::class),
        ]);

        // Every fourth rating is NULL, the others repeat so that page boundaries fall inside equal values
        for ($i = 1; $i <= $count; ++$i) {
            $book = new Book();
            $book->setTitle('Book '.$i);
            $book->setRating(0 === $i % 4 ? null : (float) ($i % 3));
            $this->entityManager->persist($book);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function createBooksWithAuthors(int $count): void
    {
        new SchemaTool($this->entityManager)->createSchema([
            $this->entityManager->getClassMetadata(Book::class),
            $this->entityManager->getClassMetadata(Category::class),
        ]);

        // Four authors, two of them sharing a name, and every fifth book without one
        $authors = [];
        foreach (['Cleo', 'Ann', 'Bob', 'Ann'] as $name) {
            $author = new Author();
            $author->setName($name);
            $this->entityManager->persist($author);
            $authors[] = $author;
        }

        for ($i = 1; $i <= $count; ++$i) {
            $book = new Book();
            $book->setTitle('Book '.$i);
            $book->setAuthor(0 === $i % 5 ? null : $authors[$i % 4]);
            $this->entityManager->persist($book);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function queryBuilder(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');
    }

    private function bookQueryBuilder(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');
    }

    /**
     * @param list<mixed> $items
     *
     * @return list<int|null>
     */
    private function idsOf(array $items): array
    {
        return array_map(static fn (Author $author) => $author->getId(), $items);
    }
}
