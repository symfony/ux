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
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\UX\Autocomplete\AutocompleteResultsExecutor;
use Symfony\UX\Autocomplete\Doctrine\DoctrineRegistryWrapper;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;

class AutocompleteResultsExecutorTest extends TestCase
{
    public function testItExecutesSecurity()
    {
        $doctrineRegistry = $this->createMock(DoctrineRegistryWrapper::class);

        $autocompleter = $this->createMock(EntityAutocompleterInterface::class);
        $autocompleter->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $executor = new AutocompleteResultsExecutor(
            $doctrineRegistry,
            $this->createMock(PropertyAccessorInterface::class),
            $this->createMock(Security::class)
        );

        $this->expectException(AccessDeniedException::class);
        $executor->fetchResults($autocompleter, 'foo', 1);
    }
}
