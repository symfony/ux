<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\ComponentRepository;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\ComponentRepository\RepositoryIdentity;
use Symfony\UX\Toolkit\ComponentRepository\OfficialRepository;
use Symfony\UX\Toolkit\ComponentRepository\RepositorySources;

/**
 * @author Jean-François Lépine
 */
class OfficialRepositoryTest extends TestCase
{
    public function testOfficialRepositoryGetContentOfExistentComponent(): void
    {
        $repository = new OfficialRepository();
        $identity = new RepositoryIdentity(RepositorySources::EMBEDDED, 'symfony', 'default');

        $finder = $repository->fetch($identity);

        $exists = $finder->files()->path('registry.json')->count();
        $this->assertEquals(1, $exists);
    }

    public function testOfficialRepositoryFailWhenComponentDoesNotExist(): void
    {
        $repository = new OfficialRepository();
        $identity = new RepositoryIdentity(RepositorySources::EMBEDDED, 'symfony', 'unexistent');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This theme does not exist.');
        $finder = $repository->fetch($identity);
    }
}
