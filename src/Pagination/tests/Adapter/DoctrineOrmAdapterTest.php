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

use Doctrine\DBAL\Logging\Middleware;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\QueryException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\UX\Pagination\Adapter\DoctrineOrmAdapter;
use Symfony\UX\Pagination\Cursor\CursorBoundary;
use Symfony\UX\Pagination\Cursor\CursorCodec;
use Symfony\UX\Pagination\Cursor\CursorOrder;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\RuntimeException;
use Symfony\UX\Pagination\Exception\UnsupportedDoctrineQueryException;
use Symfony\UX\Pagination\Tests\Fixtures\Entity\Author;
use Symfony\UX\Pagination\Tests\Fixtures\Entity\Book;
use Symfony\UX\Pagination\Tests\Fixtures\Entity\Category;
use Symfony\UX\Pagination\Tests\Fixtures\EntityManagerFactory;

#[CoversClass(DoctrineOrmAdapter::class)]
final class DoctrineOrmAdapterTest extends TestCase
{
    private EntityManager $entityManager;
    private DoctrineOrmAdapter $adapter;
    private QueryCollector $queryCollector;

    protected function setUp(): void
    {
        if (!class_exists(EntityManager::class)) {
            self::markTestSkipped('Doctrine ORM is not installed.');
        }

        try {
            $this->queryCollector = new QueryCollector();
            $this->entityManager = EntityManagerFactory::create(
                [Author::class, Book::class, Category::class],
                fn ($config) => $config->setMiddlewares([new Middleware($this->queryCollector)]),
            );

            $this->adapter = new DoctrineOrmAdapter();
        } catch (\Doctrine\ORM\ORMInvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'LazyGhost')) {
                self::markTestSkipped('Doctrine ORM requires symfony/var-exporter: '.$e->getMessage());
            }
            throw $e;
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $this->entityManager->close();
        }
    }

    public function testCountUsesDistinctForArbitraryClassJoins(): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->join(Book::class, 'b', 'WITH', 'b.author = a');

        self::assertSame(0, $this->adapter->count($qb));
    }

    public function testCountFallsBackToDistinctForNonAssociationJoins(): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->join('a.name', 'x');

        // The join analysis must not block: it falls back to COUNT(DISTINCT)
        // and lets Doctrine reject the invalid DQL itself.
        $this->expectException(QueryException::class);
        $this->adapter->count($qb);
    }

    public function testCountFallsBackToDistinctForUnresolvableJoinAliases(): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->join('x.books', 'b');

        $this->expectException(QueryException::class);
        $this->adapter->count($qb);
    }

    public function testSupportsQueryBuilder(): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');

        self::assertTrue($this->adapter->supports($qb));
        self::assertFalse($this->adapter->supports('string'));
        self::assertFalse($this->adapter->supports([]));
        self::assertFalse($this->adapter->supports(new \stdClass()));
    }

    public function testCursorContextFingerprintsDqlParametersAndApplicationContext(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->where('a.name = :name')
            ->andWhere('a.active = :filters')
            ->setParameter('name', 'Alice', 'string')
            ->setParameter('filters', [
                'date' => new \DateTimeImmutable('2026-07-28 12:34:56.123456+02:00'),
                'backed' => OrmBackedContext::Books,
                'unit' => OrmUnitContext::Catalog,
                'scalar' => true,
                'null' => null,
            ]);

        $context = json_decode($this->adapter->getCursorContext($queryBuilder, 'tenant-a'), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(DoctrineOrmAdapter::class, $context['adapter']);
        self::assertSame(Author::class, $context['entity']);
        self::assertSame('tenant-a', $context['context']);
        self::assertSame(['filters', 'name'], array_keys($context['parameters']));
        self::assertSame('2026-07-28 12:34:56.123456+02:00', $context['parameters']['filters']['value']['date']['value']);
        self::assertSame('books', $context['parameters']['filters']['value']['backed']['value']);
        self::assertSame('Catalog', $context['parameters']['filters']['value']['unit']['name']);
        self::assertSame('string', $context['parameters']['name']['type']);

        self::assertNotSame(
            $this->adapter->getCursorContext($queryBuilder, 'tenant-a'),
            $this->adapter->getCursorContext($queryBuilder, 'tenant-b'),
        );
    }

    public function testCursorContextNormalizesMappedEntityIdentifiers(): void
    {
        $author = new Author();
        $author->setName('Alice');
        $this->entityManager->persist($author);
        $this->entityManager->flush();

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->where('a = :author')
            ->setParameter('author', $author);
        $context = json_decode($this->adapter->getCursorContext($queryBuilder, null), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(
            ['id' => $author->getId()],
            $context['parameters']['author']['value']['identifier'],
        );
    }

    public function testCursorContextRejectsAnUnsavedMappedEntity(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->setParameter('author', new Author()->setName('Unsaved'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unsaved Doctrine object');
        $this->adapter->getCursorContext($queryBuilder, null);
    }

    public function testCursorContextRejectsATransientObject(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->setParameter('object', new \stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('parameter object');
        $this->adapter->getCursorContext($queryBuilder, null);
    }

    public function testCursorContextRejectsAnUnsupportedParameterType(): void
    {
        $resource = fopen('php://memory', 'r');
        \assert(false !== $resource);
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->setParameter('resource', $resource);

        try {
            $this->adapter->getCursorContext($queryBuilder, null);
            self::fail('A resource cannot produce a stable cursor context.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('resource (stream)', $exception->getMessage());
        } finally {
            fclose($resource);
        }
    }

    public function testCursorContextRejectsUnsupportedSourcesAndMultipleRoots(): void
    {
        try {
            $this->adapter->getCursorContext([], null);
            self::fail('A non-Doctrine source must be rejected.');
        } catch (InvalidArgumentException) {
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a', 'c')
            ->from(Author::class, 'a')
            ->from(Category::class, 'c');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('one Doctrine root entity');
        $this->adapter->getCursorContext($queryBuilder, null);
    }

    public function testBasicCountWithoutJoin(): void
    {
        // Create test data
        for ($i = 1; $i <= 5; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');

        self::assertSame(5, $this->adapter->count($qb));
    }

    public function testCountRejectsGroupByWithActionableAlternative(): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a.name')
            ->from(Author::class, 'a')
            ->groupBy('a.name');

        $this->expectException(UnsupportedDoctrineQueryException::class);
        $this->expectExceptionMessage('Use total(), lookahead(), or a custom pagination adapter.');
        $this->adapter->count($qb);
    }

    public function testCountRejectsHavingWithActionableAlternative(): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a.name')
            ->from(Author::class, 'a')
            ->groupBy('a.name')
            ->having('COUNT(a.id) > 1');

        $this->expectException(UnsupportedDoctrineQueryException::class);
        $this->adapter->count($qb);
    }

    public function testBasicSlice(): void
    {
        // Create test data
        for ($i = 1; $i <= 20; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->orderBy('a.id', 'ASC');

        $results = $this->adapter->slice($qb, 0, 5);
        self::assertCount(5, $results);

        $results = $this->adapter->slice($qb, 5, 5);
        self::assertCount(5, $results);

        $results = $this->adapter->slice($qb, 15, 10);
        self::assertCount(5, $results); // Only 5 left
    }

    public function testSliceWithToOneJoinExecutesOneQuery(): void
    {
        $author = new Author();
        $author->setName('Author');
        $this->entityManager->persist($author);

        for ($i = 1; $i <= 8; ++$i) {
            $book = new Book();
            $book->setTitle('Book '.$i);
            $book->setAuthor($author);
            $this->entityManager->persist($book);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('b', 'a')
            ->from(Book::class, 'b')
            ->leftJoin('b.author', 'a')
            ->orderBy('b.id', 'ASC');

        $this->queryCollector->reset();
        $results = $this->adapter->slice($qb, 0, 5);

        self::assertCount(5, $results);
        self::assertCount(1, $this->queryCollector->queries());
    }

    public function testCountWithToOneJoinDoesNotUseDistinct(): void
    {
        $author = new Author();
        $author->setName('Author');
        $this->entityManager->persist($author);

        for ($i = 1; $i <= 5; ++$i) {
            $book = new Book();
            $book->setTitle('Book '.$i);
            $book->setAuthor($author);
            $this->entityManager->persist($book);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b')
            ->leftJoin('b.author', 'a');

        $this->queryCollector->reset();

        self::assertSame(5, $this->adapter->count($qb));
        self::assertCount(1, $this->queryCollector->queries());
        self::assertStringNotContainsString('DISTINCT', strtoupper($this->queryCollector->queries()[0]));
    }

    /**
     * Test COUNT with one-to-many JOIN.
     *
     * This is a tricky case because:
     * - An author with 3 books appears 3 times in the result set after JOIN
     * - Without DISTINCT, COUNT would return 15 (5 authors × 3 books each)
     * - With DISTINCT, COUNT correctly returns 5 (unique authors)
     */
    public function testCountWithOneToManyJoin(): void
    {
        // Create 5 authors, each with 3 books
        for ($i = 1; $i <= 5; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);

            for ($j = 1; $j <= 3; ++$j) {
                $book = new Book();
                $book->setTitle('Book '.$j.' by Author '.$i);
                $book->setAuthor($author);
                $this->entityManager->persist($book);
            }

            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        // Query authors with JOIN to books
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->join('a.books', 'b');

        // Should count distinct authors, not the joined result rows
        // Expected: 5 authors (not 15 which would be the joined rows)
        $this->queryCollector->reset();

        self::assertSame(5, $this->adapter->count($qb));
        self::assertCount(1, $this->queryCollector->queries());
        self::assertStringContainsString('DISTINCT', strtoupper($this->queryCollector->queries()[0]));
    }

    /**
     * Test COUNT with many-to-many JOIN.
     *
     * This tests the scenario where books have multiple categories and
     * categories have multiple books.
     */
    public function testCountWithManyToManyJoin(): void
    {
        // Create categories
        $fiction = new Category();
        $fiction->setName('Fiction');
        $this->entityManager->persist($fiction);

        $sciFi = new Category();
        $sciFi->setName('Sci-Fi');
        $this->entityManager->persist($sciFi);

        // Create books with multiple categories
        for ($i = 1; $i <= 10; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $this->entityManager->persist($author);

            $book = new Book();
            $book->setTitle('Book '.$i);
            $book->setAuthor($author);
            $book->addCategory($fiction);

            if (0 === $i % 2) {
                $book->addCategory($sciFi); // Even books are also Sci-Fi
            }

            $this->entityManager->persist($book);
        }
        $this->entityManager->flush();

        // Query books with JOIN to categories
        $qb = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b')
            ->join('b.categories', 'c');

        // Should count distinct books, not the joined result rows
        // Expected: 10 books (not 15 which would be 10 + 5 with double category)
        self::assertSame(10, $this->adapter->count($qb));
    }

    /**
     * Test slice with JOIN to ensure data integrity.
     *
     * When slicing with JOINs, we need to ensure that:
     * - Pagination works correctly
     * - The same entity doesn't appear multiple times due to JOIN
     */
    public function testSliceWithJoin(): void
    {
        // Create authors with books
        for ($i = 1; $i <= 10; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);

            for ($j = 1; $j <= 3; ++$j) {
                $book = new Book();
                $book->setTitle('Book '.$j.' by Author '.$i);
                $book->setAuthor($author);
                $this->entityManager->persist($book);
            }

            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->join('a.books', 'b')
            ->orderBy('a.id', 'ASC');

        // First page: should get first 5 authors (not duplicate rows)
        $results = $this->adapter->slice($qb, 0, 5);

        // Note: Without DISTINCT in the select, we might get duplicate authors
        // This is a known issue with Doctrine pagination
        // The actual behavior depends on Doctrine's result handling
        self::assertIsArray($results);
    }

    public function testSliceWithFetchJoinCollectionReturnsCompleteRootEntities(): void
    {
        for ($i = 1; $i <= 8; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);

            for ($j = 1; $j <= 3; ++$j) {
                $book = new Book();
                $book->setTitle('Book '.$j.' by Author '.$i);
                $book->setAuthor($author);
                $this->entityManager->persist($book);
            }

            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a', 'b')
            ->from(Author::class, 'a')
            ->leftJoin('a.books', 'b')
            ->orderBy('a.id', 'ASC');

        $this->queryCollector->reset();
        $results = $this->adapter->slice($qb, 0, 5);

        self::assertCount(5, $results);
        self::assertCount(5, array_unique(array_map(static fn (Author $item): int => $item->getId(), $results)));

        // Identifier subquery, WHERE IN re-query, and the COUNT that
        // OffsetPaginator always runs, even though the total is unused here.
        self::assertCount(3, $this->queryCollector->queries());
    }

    /**
     * Test lookahead pagination with JOINs.
     */
    public function testLookaheadWithJoin(): void
    {
        // Create test data
        for ($i = 1; $i <= 25; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);

            for ($j = 1; $j <= 2; ++$j) {
                $book = new Book();
                $book->setTitle('Book '.$j.' by Author '.$i);
                $book->setAuthor($author);
                $this->entityManager->persist($book);
            }

            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->join('a.books', 'b')
            ->orderBy('a.id', 'ASC');

        [$items, $hasMore] = $this->adapter->sliceWithLookahead($qb, 0, 10);
        self::assertIsArray($items);
        self::assertIsBool($hasMore);
    }

    /**
     * Test cursor-based pagination.
     */
    public function testCursorPagination(): void
    {
        // Create test data
        for ($i = 1; $i <= 20; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');
        $order = $this->adapter->resolveCursorOrder($qb, ['id'], 'ASC');

        // First page (no cursor)
        $result = $this->adapter->sliceWithCursor($qb, null, 10, $order);

        self::assertInstanceOf(\Symfony\UX\Pagination\Cursor\CursorSlice::class, $result);
        self::assertIsArray($result->items);
        self::assertIsBool($result->hasNext);

        self::assertCount(10, $result->items);
        self::assertTrue($result->hasNext);
        self::assertNotNull($result->next);
    }

    public function testCursorDoesNotOverwriteApplicationParameters(): void
    {
        for ($i = 1; $i <= 12; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->where('a.id > :min_id')
            ->setParameter('min_id', 8);
        $order = $this->adapter->resolveCursorOrder($queryBuilder, ['id'], 'ASC');

        $first = $this->adapter->sliceWithCursor($queryBuilder, null, 2, $order);
        self::assertNotNull($first->next);

        $second = $this->adapter->sliceWithCursor($queryBuilder, $first->next, 2, $order);

        self::assertSame(
            [9, 10],
            array_map(static fn (Author $author): int => $author->getId(), $first->items),
        );
        self::assertSame(
            [11, 12],
            array_map(static fn (Author $author): int => $author->getId(), $second->items),
        );
    }

    /**
     * Doctrine overwrites whatever is bound under the name it generates, which
     * would silently return the wrong rows.
     */
    public function testCursorRejectsQueryParametersCollidingWithDoctrineCursorParameters(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->where('a.id <= :a_id_0')
            ->setParameter('a_id_0', 6);
        $order = $this->adapter->resolveCursorOrder($queryBuilder, ['id'], 'ASC');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The query parameter ":a_id_0" collides with the parameter Doctrine generates for cursor field "id". Rename it.');

        $this->adapter->sliceWithCursor($queryBuilder, null, 2, $order);
    }

    public function testCursorRejectsCollidingParametersForAppendedTieBreakers(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->where('a.id <= :a_id_1')
            ->setParameter('a_id_1', 6);
        // 'id' is appended as a tie-breaker, landing at index 1.
        $order = $this->adapter->resolveCursorOrder($queryBuilder, ['name'], 'ASC');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The query parameter ":a_id_1" collides with the parameter Doctrine generates for cursor field "id". Rename it.');

        $this->adapter->sliceWithCursor($queryBuilder, null, 2, $order);
    }

    /**
     * Test cursor pagination with JOINs.
     *
     * Note: JOIN queries with cursor pagination can have tricky behavior due to
     * duplicate rows. This test verifies basic functionality works without errors.
     * For precise pagination with JOINs, consider using DISTINCT in your query
     * or using lookahead pagination.
     */
    public function testCursorPaginationWithJoin(): void
    {
        // Create authors with books
        for ($i = 1; $i <= 15; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);

            for ($j = 1; $j <= 2; ++$j) {
                $book = new Book();
                $book->setTitle('Book '.$j.' by Author '.$i);
                $book->setAuthor($author);
                $this->entityManager->persist($book);
            }

            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT a')
            ->from(Author::class, 'a')
            ->join('a.books', 'b');
        $order = $this->adapter->resolveCursorOrder($qb, ['id'], 'ASC');

        $result = $this->adapter->sliceWithCursor($qb, null, 5, $order);

        self::assertInstanceOf(\Symfony\UX\Pagination\Cursor\CursorSlice::class, $result);
        self::assertIsBool($result->hasNext);
        self::assertCount(5, $result->items);
    }

    /**
     * A collection join paginates through an identifier subquery and a WHERE IN
     * re-query. Only the subquery is reversed, so rows keep the display order.
     */
    public function testCursorBackwardNavigationWithCollectionJoinKeepsDisplayOrder(): void
    {
        for ($i = 1; $i <= 15; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);

            for ($j = 1; $j <= 2; ++$j) {
                $book = new Book();
                $book->setTitle('Book '.$j.' by Author '.$i);
                $book->setAuthor($author);
                $this->entityManager->persist($book);
            }

            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT a')
            ->from(Author::class, 'a')
            ->join('a.books', 'b');
        $order = $this->adapter->resolveCursorOrder($qb, ['id'], 'ASC');

        $first = $this->adapter->sliceWithCursor($qb, null, 5, $order);
        self::assertNotNull($first->next);

        $second = $this->adapter->sliceWithCursor($qb, $first->next, 5, $order);
        self::assertSame([6, 7, 8, 9, 10], $this->idsOf($second->items));
        self::assertNotNull($second->previous);

        $back = $this->adapter->sliceWithCursor($qb, $second->previous, 5, $order);
        self::assertSame([1, 2, 3, 4, 5], $this->idsOf($back->items));
    }

    public function testCursorPaginatesSelectNewDtoQueriesWithPublicProperties(): void
    {
        $this->createAuthors(5);

        $qb = $this->entityManager->createQueryBuilder()
            ->select(\sprintf('NEW %s(a.id, a.name)', OrmAuthorRow::class))
            ->from(Author::class, 'a');
        $order = $this->adapter->resolveCursorOrder($qb, ['id'], 'ASC');

        $first = $this->adapter->sliceWithCursor($qb, null, 2, $order);
        self::assertNotNull($first->next);

        $second = $this->adapter->sliceWithCursor($qb, $first->next, 2, $order);

        self::assertSame([1, 2], array_map(static fn (OrmAuthorRow $row): int => $row->id, $first->items));
        self::assertSame([3, 4], array_map(static fn (OrmAuthorRow $row): int => $row->id, $second->items));
    }

    public function testCursorRejectsSelectNewDtoQueriesHidingTheOrderedFields(): void
    {
        $this->createAuthors(5);

        $qb = $this->entityManager->createQueryBuilder()
            ->select(\sprintf('NEW %s(a.id, a.name)', OrmPrivateAuthorRow::class))
            ->from(Author::class, 'a');
        $order = $this->adapter->resolveCursorOrder($qb, ['id'], 'ASC');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('Cannot read the cursor fields back from an item of type "%s".', OrmPrivateAuthorRow::class));

        $this->adapter->sliceWithCursor($qb, null, 2, $order);
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

    /**
     * @param list<mixed> $items
     *
     * @return list<int>
     */
    private function idsOf(array $items): array
    {
        return array_map(static fn (Author $author): int => $author->getId(), $items);
    }

    public function testCountThrowsForNonQueryBuilder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a Doctrine ORM QueryBuilder.');
        $this->adapter->count('not a query builder');
    }

    public function testCountRejectsMultipleRootEntities(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a', 'c')
            ->from(Author::class, 'a')
            ->from(Category::class, 'c');

        $this->expectException(UnsupportedDoctrineQueryException::class);
        $this->expectExceptionMessage('multiple root aliases');
        $this->adapter->count($queryBuilder);
    }

    public function testSliceThrowsForNonQueryBuilder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a Doctrine ORM QueryBuilder.');
        $this->adapter->slice('not a query builder', 0, 10);
    }

    public function testLookaheadThrowsForNonQueryBuilder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a Doctrine ORM QueryBuilder.');
        $this->adapter->sliceWithLookahead('not a query builder', 0, 10);
    }

    public function testCursorThrowsForNonQueryBuilder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a Doctrine ORM QueryBuilder.');
        $this->adapter->sliceWithCursor('not a query builder', null, 10, CursorOrder::byFields(['id'], 'ASC'));
    }

    public function testCursorFieldResolutionRejectsNonQueryBuilders(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a Doctrine ORM QueryBuilder.');

        $this->adapter->resolveCursorFields([], 'id');
    }

    public function testCursorFieldsMustBeNonEmptyStrings(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cursor fields must be non-empty strings.');

        $this->adapter->resolveCursorFields($queryBuilder, []);
    }

    public function testCursorOrderMustBeExplicit(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires an explicit orderBy()');

        $this->adapter->resolveCursorOrder($queryBuilder, null, null);
    }

    public function testCursorRejectsAnOpaqueOrder(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a field-based cursor order');

        $this->adapter->sliceWithCursor($queryBuilder, null, 10, CursorOrder::byIdentity('remote-order'));
    }

    public function testCursorRejectsInvalidDirection(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('direction must be "ASC" or "DESC"');
        $this->adapter->resolveCursorOrder($queryBuilder, ['id'], 'sideways');
    }

    public function testCursorRejectsMissingFromClause(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()->select('1');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('QueryBuilder has no FROM clause.');
        $this->adapter->resolveCursorOrder($queryBuilder, ['id'], 'ASC');
    }

    public function testCursorRejectsUnknownField(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cursor field "unknown"');
        $this->adapter->resolveCursorOrder($queryBuilder, ['unknown'], 'ASC');
    }

    /**
     * Test that COUNT works with WHERE clauses.
     */
    public function testCountWithWhereClause(): void
    {
        // Create test data
        for ($i = 1; $i <= 10; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $author->setActive($i <= 5); // Only first 5 are active
            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->where('a.active = :active')
            ->setParameter('active', true);

        self::assertSame(5, $this->adapter->count($qb));
    }

    /**
     * Test COUNT with complex WHERE and JOIN.
     */
    public function testCountWithWhereAndJoin(): void
    {
        // Create authors, some active and some not
        for ($i = 1; $i <= 10; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $author->setActive($i <= 5); // Only first 5 are active

            // Each author has 2 books
            for ($j = 1; $j <= 2; ++$j) {
                $book = new Book();
                $book->setTitle('Book '.$j.' by Author '.$i);
                $book->setAuthor($author);
                $this->entityManager->persist($book);
            }

            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->join('a.books', 'b')
            ->where('a.active = :active')
            ->setParameter('active', true);

        // Should count 5 active authors (with DISTINCT)
        self::assertSame(5, $this->adapter->count($qb));
    }

    /**
     * Test single-field cursor pagination (backward compatibility).
     */
    public function testSingleFieldCursorPagination(): void
    {
        // Create test data with sequential IDs
        for ($i = 1; $i <= 20; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');
        $order = $this->adapter->resolveCursorOrder($qb, ['id'], 'ASC');

        // First page (no cursor)
        $result = $this->adapter->sliceWithCursor($qb, null, 5, $order);

        self::assertCount(5, $result->items);
        self::assertTrue($result->hasNext);
        self::assertNotNull($result->next);
        self::assertNull($result->previous);

        // Second page using nextCursor
        $result2 = $this->adapter->sliceWithCursor($qb, $result->next, 5, $order);

        self::assertCount(5, $result2->items);
        self::assertTrue($result2->hasNext);
        self::assertNotNull($result2->next);

        // Verify no overlaps between pages
        $firstIds = array_map(static fn (Author $item) => $item->getId(), $result->items);
        $secondIds = array_map(static fn (Author $item) => $item->getId(), $result2->items);
        self::assertEmpty(array_intersect($firstIds, $secondIds), 'Pages should not have overlapping items');
    }

    /**
     * Test backward navigation: the previousCursor of page 2 must
     * return the items of page 1, in display order.
     */
    public function testCursorBackwardNavigationReturnsPreviousPage(): void
    {
        for ($i = 1; $i <= 20; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');
        $order = $this->adapter->resolveCursorOrder($qb, ['id'], 'ASC');

        $page1 = $this->adapter->sliceWithCursor($qb, null, 5, $order);
        $page2 = $this->adapter->sliceWithCursor($qb, $page1->next, 5, $order);

        self::assertNotNull($page2->previous);

        $back = $this->adapter->sliceWithCursor($qb, $page2->previous, 5, $order);

        $page1Ids = array_map(static fn (Author $item) => $item->getId(), $page1->items);
        $backIds = array_map(static fn (Author $item) => $item->getId(), $back->items);

        self::assertSame($page1Ids, $backIds, 'Backward navigation must return the previous page in display order');
        self::assertNull($back->previous, 'First page has no previous page');
        self::assertNotNull($back->next);

        // Going forward again returns page 2
        $forwardAgain = $this->adapter->sliceWithCursor($qb, $back->next, 5, $order);
        $page2Ids = array_map(static fn (Author $item) => $item->getId(), $page2->items);
        self::assertSame($page2Ids, array_map(static fn (Author $item) => $item->getId(), $forwardAgain->items));
    }

    /**
     * Test backward navigation from a middle page keeps a previousCursor.
     */
    public function testCursorBackwardNavigationFromMiddlePage(): void
    {
        for ($i = 1; $i <= 20; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');
        $order = $this->adapter->resolveCursorOrder($qb, ['id'], 'ASC');

        $page1 = $this->adapter->sliceWithCursor($qb, null, 5, $order);
        $page2 = $this->adapter->sliceWithCursor($qb, $page1->next, 5, $order);
        $page3 = $this->adapter->sliceWithCursor($qb, $page2->next, 5, $order);

        $back = $this->adapter->sliceWithCursor($qb, $page3->previous, 5, $order);

        $page2Ids = array_map(static fn (Author $item) => $item->getId(), $page2->items);
        self::assertSame($page2Ids, array_map(static fn (Author $item) => $item->getId(), $back->items));
        self::assertNotNull($back->previous, 'Page 1 still exists before page 2');
    }

    /**
     * Test composite cursor with 2 fields (price, id).
     *
     * This tests the critical case where sorting by a non-unique field
     * (price) could cause duplicates or skips. Using a composite cursor
     * with ID as tie-breaker ensures deterministic ordering.
     */
    public function testCompositeCursorWithTwoFields(): void
    {
        // Create books with duplicate prices
        $prices = [10.0, 10.0, 10.0, 20.0, 20.0, 30.0, 30.0, 30.0, 40.0, 50.0];

        foreach ($prices as $i => $price) {
            $author = new Author();
            $author->setName('Author '.($i + 1));
            $this->entityManager->persist($author);

            $book = new Book();
            $book->setTitle('Book '.($i + 1));
            $book->setPrice($price);
            $book->setAuthor($author);
            $this->entityManager->persist($book);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');
        $order = $this->adapter->resolveCursorOrder($qb, ['price', 'id'], 'ASC');

        // First page with composite cursor
        $result = $this->adapter->sliceWithCursor($qb, null, 3, $order);

        self::assertCount(3, $result->items);
        self::assertTrue($result->hasNext);
        self::assertNotNull($result->next);

        // Verify first page has books with price 10.0
        foreach ($result->items as $book) {
            self::assertSame(10.0, $book->getPrice());
        }

        // Second page
        $result2 = $this->adapter->sliceWithCursor($qb, $result->next, 3, $order);

        self::assertCount(3, $result2->items);
        self::assertTrue($result2->hasNext);

        // Verify ordering and no duplicates
        $firstIds = array_map(static fn (Book $item): int => $item->getId(), $result->items);
        $secondIds = array_map(static fn (Book $item): int => $item->getId(), $result2->items);
        self::assertEmpty(array_intersect($firstIds, $secondIds), 'Pages should not have overlapping items');

        // Third page
        $result3 = $this->adapter->sliceWithCursor($qb, $result2->next, 3, $order);

        self::assertCount(3, $result3->items);
        self::assertTrue($result3->hasNext);

        // Collect all IDs across all pages
        $thirdIds = array_map(static fn (Book $item): int => $item->getId(), $result3->items);
        $allIds = array_merge($firstIds, $secondIds, $thirdIds);

        // Verify no duplicates across all pages
        self::assertCount(9, $allIds);
        self::assertCount(9, array_unique($allIds));
    }

    public function testSingleNonUniqueCursorFieldAutomaticallyUsesIdentifierTieBreaker(): void
    {
        $names = ['Alice', 'Alice', 'Alice', 'Bob', 'Bob', 'Charlie', 'Charlie', 'Charlie'];

        foreach ($names as $name) {
            $author = new Author();
            $author->setName($name);
            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');
        $order = $this->adapter->resolveCursorOrder($qb, ['name'], 'ASC');

        $seenIds = [];
        $cursor = null;

        do {
            $result = $this->adapter->sliceWithCursor($qb, $cursor, 2, $order);
            $pageIds = array_map(static fn (Author $item): int => $item->getId(), $result->items);
            $seenIds = array_merge($seenIds, $pageIds);
            $cursor = $result->next;
        } while (null !== $cursor);

        self::assertCount(8, $seenIds);
        self::assertCount(8, array_unique($seenIds));
    }

    /**
     * Test composite cursor with DESC direction.
     */
    public function testCompositeCursorWithDescDirection(): void
    {
        // Create books with prices in ascending order
        for ($i = 1; $i <= 10; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $this->entityManager->persist($author);

            $book = new Book();
            $book->setTitle('Book '.$i);
            $book->setPrice((float) ($i * 10));
            $book->setAuthor($author);
            $this->entityManager->persist($book);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');
        $order = $this->adapter->resolveCursorOrder($qb, ['price', 'id'], 'DESC');

        // First page with DESC order
        $result = $this->adapter->sliceWithCursor($qb, null, 3, $order);

        self::assertCount(3, $result->items);
        self::assertTrue($result->hasNext);

        // Verify DESC ordering - highest prices first
        $firstBook = $result->items[0];
        self::assertSame(100.0, $firstBook->getPrice());

        // Second page
        $result2 = $this->adapter->sliceWithCursor($qb, $result->next, 3, $order);

        self::assertCount(3, $result2->items);
        self::assertTrue($result2->hasNext);

        // Verify no overlaps
        $firstIds = array_map(static fn (Book $item): int => $item->getId(), $result->items);
        $secondIds = array_map(static fn (Book $item): int => $item->getId(), $result2->items);
        self::assertEmpty(array_intersect($firstIds, $secondIds));
    }

    /**
     * Test composite cursor with 3 fields.
     */
    public function testCompositeCursorWithThreeFields(): void
    {
        // Create authors with duplicate names for testing
        $names = ['Alice', 'Alice', 'Alice', 'Bob', 'Bob', 'Charlie'];

        foreach ($names as $i => $name) {
            $author = new Author();
            $author->setName($name);
            $this->entityManager->persist($author);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');
        $order = $this->adapter->resolveCursorOrder($qb, ['name', 'active', 'id'], 'ASC');

        // Use composite cursor with 3 fields: [name, active, id]
        $result = $this->adapter->sliceWithCursor($qb, null, 2, $order);

        self::assertCount(2, $result->items);
        self::assertTrue($result->hasNext);
        self::assertNotNull($result->next);

        // Second page
        $result2 = $this->adapter->sliceWithCursor($qb, $result->next, 2, $order);

        self::assertCount(2, $result2->items);
        self::assertTrue($result2->hasNext);

        // Verify no duplicates
        $firstIds = array_map(static fn (Author $item): int => $item->getId(), $result->items);
        $secondIds = array_map(static fn (Author $item): int => $item->getId(), $result2->items);
        self::assertEmpty(array_intersect($firstIds, $secondIds));
    }

    /**
     * Test encoding and decoding of composite cursors.
     */
    public function testCompositeCursorBoundaryRoundTrip(): void
    {
        // Create test data
        for ($i = 1; $i <= 5; ++$i) {
            $author = new Author();
            $author->setName('Author '.$i);
            $this->entityManager->persist($author);

            $book = new Book();
            $book->setTitle('Book '.$i);
            $book->setPrice((float) ($i * 10));
            $book->setAuthor($author);
            $this->entityManager->persist($book);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');
        $order = $this->adapter->resolveCursorOrder($qb, ['price', 'id'], 'ASC');

        // Get first page
        $result = $this->adapter->sliceWithCursor($qb, null, 2, $order);

        self::assertNotNull($result->next);

        // One opaque token, whatever the number of ordered fields.
        self::assertCount(1, $result->next->values);
        self::assertIsString($result->next->values[0]);

        // Use the cursor for next page - should work without errors
        $result2 = $this->adapter->sliceWithCursor($qb, $result->next, 2, $order);
        self::assertInstanceOf(\Symfony\UX\Pagination\Cursor\CursorSlice::class, $result2);
        self::assertNotEmpty($result2->items);
    }

    public function testCursorRejectsNullableScalarField(): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cursor field "rating" must be non-nullable.');
        $this->adapter->resolveCursorOrder($qb, ['rating'], 'ASC');
    }

    public function testCursorRejectsUnsupportedDoctrineFieldTypes(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');

        // Rejected while resolving the order, so the first page already fails.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Doctrine type "json" of cursor field "metadata" is not supported.');

        $this->adapter->resolveCursorOrder($queryBuilder, ['metadata'], 'ASC');
    }

    public function testCursorPaginatesOnDateFields(): void
    {
        $author = new Author();
        $author->setName('Author');
        $this->entityManager->persist($author);

        for ($i = 1; $i <= 5; ++$i) {
            $book = new Book();
            $book->setTitle('Book '.$i);
            $book->setPrice((float) $i);
            $book->setPublishedAt(new \DateTimeImmutable(\sprintf('2024-01-%02d 10:00:00', $i)));
            $book->setAuthor($author);
            $this->entityManager->persist($book);
        }
        $this->entityManager->flush();

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');
        $order = $this->adapter->resolveCursorOrder($queryBuilder, ['publishedAt'], 'ASC');

        $first = $this->adapter->sliceWithCursor($queryBuilder, null, 2, $order);
        self::assertNotNull($first->next);

        $second = $this->adapter->sliceWithCursor($queryBuilder, $first->next, 2, $order);

        $firstIds = array_map(static fn (Book $book): int => $book->getId(), $first->items);
        $secondIds = array_map(static fn (Book $book): int => $book->getId(), $second->items);

        self::assertSame([1, 2], $firstIds);
        self::assertSame([3, 4], $secondIds);
    }

    public function testCursorRejectsAnUndecodableToken(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');
        $order = $this->adapter->resolveCursorOrder($queryBuilder, ['publishedAt'], 'ASC');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cursor value.');

        $this->adapter->sliceWithCursor($queryBuilder, new CursorBoundary(['not-a-doctrine-cursor!']), 10, $order);
    }

    public function testCursorRejectsAMalformedBoundary(): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');
        $order = $this->adapter->resolveCursorOrder($queryBuilder, ['price', 'id'], 'ASC');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A Doctrine ORM cursor boundary must hold exactly one non-empty cursor token.');

        $this->adapter->sliceWithCursor($queryBuilder, new CursorBoundary([10, 1]), 10, $order);
    }

    /**
     * Test error handling: cursor values count mismatch.
     */
    public function testCompositeCursorMismatchThrowsException(): void
    {
        $author = new Author();
        $author->setName('Author 1');
        $this->entityManager->persist($author);

        // Create multiple books so we have a nextCursor
        for ($i = 1; $i <= 5; ++$i) {
            $book = new Book();
            $book->setTitle('Book '.$i);
            $book->setPrice((float) ($i * 10));
            $book->setAuthor($author);
            $this->entityManager->persist($book);
        }
        $this->entityManager->flush();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');
        $compositeOrder = $this->adapter->resolveCursorOrder($qb, ['price', 'id'], 'ASC');

        // Get cursor with 2 fields
        $result = $this->adapter->sliceWithCursor($qb, null, 2, $compositeOrder);

        self::assertNotNull($result->next, 'Expected nextCursor to be set');

        // The adapter cannot tell an opaque token apart, so replaying a boundary
        // against another order is caught by the fingerprint the codec signs in.
        $codec = new CursorCodec('secret');
        $context = $this->adapter->getCursorContext($qb, null);
        $token = $codec->encode($result->next->values, true, $compositeOrder->getFingerprint(), $context);

        $singleFieldOrder = $this->adapter->resolveCursorOrder($qb, ['id'], 'ASC');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The cursor does not match this pagination order.');

        $codec->decode($token, $singleFieldOrder->getFingerprint(), $context);
    }
}

final class OrmAuthorRow
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}

final class OrmPrivateAuthorRow
{
    public function __construct(
        private int $id,
        private string $name,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

enum OrmBackedContext: string
{
    case Books = 'books';
}

enum OrmUnitContext
{
    case Catalog;
}

final class QueryCollector extends AbstractLogger
{
    /** @var list<string> */
    private array $queries = [];

    public function log($level, $message, array $context = []): void
    {
        if (str_starts_with((string) $message, 'Executing ') && isset($context['sql']) && \is_string($context['sql'])) {
            $this->queries[] = $context['sql'];
        }
    }

    public function reset(): void
    {
        $this->queries = [];
    }

    /**
     * @return list<string>
     */
    public function queries(): array
    {
        return $this->queries;
    }
}
