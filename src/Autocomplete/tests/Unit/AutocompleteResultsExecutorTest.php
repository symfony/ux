<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Unit;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\Autocomplete\AutocompleteResultsExecutor;
use Symfony\UX\Autocomplete\Doctrine\DoctrineRegistryWrapper;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;

class AutocompleteResultsExecutorTest extends TestCase
{
    public function testItExecutesTheResults()
    {
        $doctrineRegistry = $this->createMock(DoctrineRegistryWrapper::class);
        $doctrineRegistry->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->createMock(EntityRepository::class));

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $autocompleter = $this->createMock(EntityAutocompleterInterface::class);
        $autocompleter->expects($this->once())
            ->method('createFilteredQueryBuilder')
            ->willReturn($queryBuilder);
        $autocompleter->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnCallback(function (object $object) {
                return $object->id;
            });
        $autocompleter->expects($this->exactly(2))
            ->method('getLabel')
            ->willReturnCallback(function (object $object) {
                return $object->name;
            });

        $result1 = new \stdClass();
        $result1->id = 1;
        $result1->name = 'Result 1';
        $result2 = new \stdClass();
        $result2->id = 2;
        $result2->name = 'Result 2';

        $mockQuery = $this->createMock(AbstractQuery::class);
        $mockQuery->expects($this->once())
            ->method('execute')
            ->willReturn([$result1, $result2]);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($mockQuery);

        $executor = new AutocompleteResultsExecutor($doctrineRegistry);
        $this->assertEquals([
            ['value' => 1, 'text' => 'Result 1'],
            ['value' => 2, 'text' => 'Result 2'],
        ], $executor->fetchResults($autocompleter, 'foo'));
    }

    public function testItExecutesSecurity()
    {
        $doctrineRegistry = $this->createMock(DoctrineRegistryWrapper::class);

        $autocompleter = $this->createMock(EntityAutocompleterInterface::class);
        $autocompleter->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $executor = new AutocompleteResultsExecutor(
            $doctrineRegistry,
            $this->createMock(Security::class)
        );

        $this->expectException(AccessDeniedException::class);
        $executor->fetchResults($autocompleter, 'foo');
    }
}
