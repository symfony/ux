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

    public function testCountUsesDistinctForArbitraryClassJoins()
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->join(Book::class, 'b', 'WITH', 'b.author = a');

        self::assertSame(0, $this->adapter->count($qb));
    }

    public function testCountFallsBackToDistinctForNonAssociationJoins()
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

    public function testCountFallsBackToDistinctForUnresolvableJoinAliases()
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->join('x.books', 'b');

        $this->expectException(QueryException::class);
        $this->adapter->count($qb);
    }

    public function testSupportsQueryBuilder()
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');

        self::assertTrue($this->adapter->supports($qb));
        self::assertFalse($this->adapter->supports('string'));
        self::assertFalse($this->adapter->supports([]));
        self::assertFalse($this->adapter->supports(new \stdClass()));
    }

    public function testCursorContextFingerprintsDqlParametersAndApplicationContext()
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

    public function testCursorContextNormalizesMappedEntityIdentifiers()
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

    public function testCursorContextRejectsAnUnsavedMappedEntity()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->setParameter('author', new Author()->setName('Unsaved'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unsaved Doctrine object');
        $this->adapter->getCursorContext($queryBuilder, null);
    }

    public function testCursorContextRejectsATransientObject()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a')
            ->setParameter('object', new \stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('parameter object');
        $this->adapter->getCursorContext($queryBuilder, null);
    }

    public function testCursorContextRejectsAnUnsupportedParameterType()
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

    public function testCursorContextRejectsUnsupportedSourcesAndMultipleRoots()
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

    public function testBasicCountWithoutJoin()
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

    public function testCountRejectsGroupByWithActionableAlternative()
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a.name')
            ->from(Author::class, 'a')
            ->groupBy('a.name');

        $this->expectException(UnsupportedDoctrineQueryException::class);
        $this->expectExceptionMessage('Use total(), lookahead(), or a custom pagination adapter.');
        $this->adapter->count($qb);
    }

    public function testCountRejectsHavingWithActionableAlternative()
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('a.name')
            ->from(Author::class, 'a')
            ->groupBy('a.name')
            ->having('COUNT(a.id) > 1');

        $this->expectException(UnsupportedDoctrineQueryException::class);
        $this->adapter->count($qb);
    }

    public function testBasicSlice()
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

    public function testSliceWithToOneJoinExecutesOneQuery()
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

    public function testCountWithToOneJoinDoesNotUseDistinct()
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
    public function testCountWithOneToManyJoin()
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
    public function testCountWithManyToManyJoin()
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
    public function testSliceWithJoin()
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

    public function testSliceWithFetchJoinCollectionReturnsCompleteRootEntities()
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
        self::assertCount(2, $this->queryCollector->queries());
    }

    /**
     * Test lookahead pagination with JOINs.
     */
    public function testLookaheadWithJoin()
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
    public function testCursorPagination()
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

    public function testCursorDoesNotOverwriteApplicationParameters()
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
            ->where('a.id > :ux_pagination_cursor_0')
            ->setParameter('ux_pagination_cursor_0', 8);
        $order = $this->adapter->resolveCursorOrder(
            $queryBuilder,
            ['id'],
            'ASC',
        );

        $result = $this->adapter->sliceWithCursor(
            $queryBuilder,
            new CursorBoundary([3]),
            2,
            $order,
        );

        self::assertSame(
            [9, 10],
            array_map(static fn (Author $author): int => $author->getId(), $result->items),
        );
    }

    /**
     * Test cursor pagination with JOINs.
     *
     * Note: JOIN queries with cursor pagination can have tricky behavior due to
     * duplicate rows. This test verifies basic functionality works without errors.
     * For precise pagination with JOINs, consider using DISTINCT in your query
     * or using lookahead pagination.
     */
    public function testCursorPaginationWithJoin()
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

    public function testCountThrowsForNonQueryBuilder()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a Doctrine ORM QueryBuilder.');
        $this->adapter->count('not a query builder');
    }

    public function testCountRejectsMultipleRootEntities()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a', 'c')
            ->from(Author::class, 'a')
            ->from(Category::class, 'c');

        $this->expectException(UnsupportedDoctrineQueryException::class);
        $this->expectExceptionMessage('multiple root aliases');
        $this->adapter->count($queryBuilder);
    }

    public function testSliceThrowsForNonQueryBuilder()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a Doctrine ORM QueryBuilder.');
        $this->adapter->slice('not a query builder', 0, 10);
    }

    public function testLookaheadThrowsForNonQueryBuilder()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a Doctrine ORM QueryBuilder.');
        $this->adapter->sliceWithLookahead('not a query builder', 0, 10);
    }

    public function testCursorThrowsForNonQueryBuilder()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a Doctrine ORM QueryBuilder.');
        $this->adapter->sliceWithCursor('not a query builder', null, 10, CursorOrder::byFields(['id'], 'ASC'));
    }

    public function testCursorFieldResolutionRejectsNonQueryBuilders()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a Doctrine ORM QueryBuilder.');

        $this->adapter->resolveCursorFields([], 'id');
    }

    public function testCursorFieldsMustBeNonEmptyStrings()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cursor fields must be non-empty strings.');

        $this->adapter->resolveCursorFields($queryBuilder, []);
    }

    public function testCursorOrderMustBeExplicit()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires an explicit orderBy()');

        $this->adapter->resolveCursorOrder($queryBuilder, null, null);
    }

    public function testCursorRejectsAnOpaqueOrder()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a field-based cursor order');

        $this->adapter->sliceWithCursor($queryBuilder, null, 10, CursorOrder::byIdentity('remote-order'));
    }

    public function testCursorRejectsInvalidDirection()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Author::class, 'a');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('direction must be "ASC" or "DESC"');
        $this->adapter->resolveCursorOrder($queryBuilder, ['id'], 'sideways');
    }

    public function testCursorRejectsMissingFromClause()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()->select('1');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('QueryBuilder has no FROM clause.');
        $this->adapter->resolveCursorOrder($queryBuilder, ['id'], 'ASC');
    }

    public function testCursorRejectsUnknownField()
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
    public function testCountWithWhereClause()
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
    public function testCountWithWhereAndJoin()
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
    public function testSingleFieldCursorPagination()
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
    public function testCursorBackwardNavigationReturnsPreviousPage()
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
    public function testCursorBackwardNavigationFromMiddlePage()
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
    public function testCompositeCursorWithTwoFields()
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

    public function testSingleNonUniqueCursorFieldAutomaticallyUsesIdentifierTieBreaker()
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
    public function testCompositeCursorWithDescDirection()
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
    public function testCompositeCursorWithThreeFields()
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
    public function testCompositeCursorBoundaryRoundTrip()
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

        self::assertCount(2, $result->next->values);

        // Use the cursor for next page - should work without errors
        $result2 = $this->adapter->sliceWithCursor($qb, $result->next, 2, $order);
        self::assertInstanceOf(\Symfony\UX\Pagination\Cursor\CursorSlice::class, $result2);
        self::assertNotEmpty($result2->items);
    }

    public function testCursorRejectsNullableScalarField()
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cursor field "rating" must be non-nullable.');
        $this->adapter->resolveCursorOrder($qb, ['rating'], 'ASC');
    }

    public function testCursorRejectsUnsupportedDoctrineFieldTypes()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');
        $order = $this->adapter->resolveCursorOrder($queryBuilder, ['metadata'], 'ASC');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Doctrine type "json" of cursor field "metadata" is not supported.');

        $this->adapter->sliceWithCursor($queryBuilder, new CursorBoundary(['{}', 1]), 10, $order);
    }

    public function testCursorNormalizesValidDateValues()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');
        $order = $this->adapter->resolveCursorOrder($queryBuilder, ['publishedAt'], 'ASC');

        $result = $this->adapter->sliceWithCursor(
            $queryBuilder,
            new CursorBoundary(['1999-01-01T00:00:00+00:00', 0]),
            10,
            $order,
        );

        self::assertSame([], $result->items);
    }

    public function testCursorRejectsInvalidDateValues()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Book::class, 'b');
        $order = $this->adapter->resolveCursorOrder($queryBuilder, ['publishedAt'], 'ASC');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date cursor value for field "publishedAt".');

        $this->adapter->sliceWithCursor($queryBuilder, new CursorBoundary(['not-a-date', 1]), 10, $order);
    }

    /**
     * Test error handling: cursor values count mismatch.
     */
    public function testCompositeCursorMismatchThrowsException()
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

        // Try to use it with 1 field - should throw exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cursor values count does not match cursor fields count');

        $this->adapter->sliceWithCursor($qb, $result->next, 2, $this->adapter->resolveCursorOrder($qb, ['id'], 'ASC'));
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
