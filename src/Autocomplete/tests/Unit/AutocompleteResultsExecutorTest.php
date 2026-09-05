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

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\Autocomplete\AutocompleteResults;
use Symfony\UX\Autocomplete\AutocompleteResultsExecutor;
use Symfony\UX\Autocomplete\AutocompleterInterface;
use Symfony\UX\Autocomplete\Doctrine\DoctrineRegistryWrapper;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;

class AutocompleteResultsExecutorTest extends TestCase
{
    public function testItExecutesSecurity()
    {
        $doctrineRegistry = $this->createStub(DoctrineRegistryWrapper::class);

        $autocompleter = $this->createMock(EntityAutocompleterInterface::class);
        $autocompleter->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $executor = new AutocompleteResultsExecutor(
            $doctrineRegistry,
            $this->createStub(PropertyAccessorInterface::class),
            $this->createStub(Security::class)
        );

        $this->expectException(AccessDeniedException::class);
        $executor->fetchResults($autocompleter, 'foo', 1);
    }

    public function testItExecutesSecurityForGenericAutocompleter()
    {
        $autocompleter = $this->createMock(AutocompleterInterface::class);
        $autocompleter->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $executor = new AutocompleteResultsExecutor(
            null,
            $this->createStub(PropertyAccessorInterface::class),
            $this->createStub(Security::class)
        );

        $this->expectException(AccessDeniedException::class);
        $executor->fetchResults($autocompleter, 'foo', 1);
    }

    public function testItDelegatesToGenericAutocompleter()
    {
        $expectedResults = new AutocompleteResults(
            [['value' => '1', 'text' => 'Result 1']],
            false,
        );

        $autocompleter = $this->createMock(AutocompleterInterface::class);
        $autocompleter->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);
        $autocompleter->expects($this->once())
            ->method('fetchResults')
            ->with('foo', 1)
            ->willReturn($expectedResults);

        $executor = new AutocompleteResultsExecutor(
            null,
            $this->createStub(PropertyAccessorInterface::class),
            $this->createStub(Security::class)
        );

        $results = $executor->fetchResults($autocompleter, 'foo', 1);
        $this->assertSame($expectedResults, $results);
    }

    public function testGenericAutocompleterWithoutSecurity()
    {
        $expectedResults = new AutocompleteResults([], false);

        $autocompleter = $this->createMock(AutocompleterInterface::class);
        $autocompleter->expects($this->never())
            ->method('isGranted');
        $autocompleter->expects($this->once())
            ->method('fetchResults')
            ->with('bar', 2)
            ->willReturn($expectedResults);

        $executor = new AutocompleteResultsExecutor(
            null,
            $this->createStub(PropertyAccessorInterface::class),
        );

        $results = $executor->fetchResults($autocompleter, 'bar', 2);
        $this->assertSame($expectedResults, $results);
    }
}
