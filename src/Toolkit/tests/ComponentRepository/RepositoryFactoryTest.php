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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Toolkit\ComponentRepository\GithubRepository;
use Symfony\UX\Toolkit\ComponentRepository\OfficialRepository;
use Symfony\UX\Toolkit\ComponentRepository\RepositoryFactory;
use Symfony\UX\Toolkit\ComponentRepository\RepositoryIdentity;
use Symfony\UX\Toolkit\ComponentRepository\RepositorySources;

/**
 * @author Jean-François Lépine
 */
class RepositoryFactoryTest extends KernelTestCase
{
    /**
     * @dataProvider providesSources
     */
    public function testItShouldFactoryRepositoryAccordingToItsName(
        int $type,
        ?string $expectedInstance,
        bool $shouldThrowException = false,
    ): void {
        $this->bootKernel();
        $factory = static::getContainer()->get(RepositoryFactory::class);

        if ($shouldThrowException) {
            $this->expectException(\InvalidArgumentException::class);
        }

        $result = $factory->factory(new RepositoryIdentity($type, 'myvendor', 'mypackage'));

        if ($shouldThrowException) {
            return;
        }

        $this->assertInstanceOf($expectedInstance, $result);
    }

    public static function providesSources(): array
    {
        return [
            [RepositorySources::EMBEDDED, OfficialRepository::class, false],
            [RepositorySources::GITHUB, GithubRepository::class, false],
            [99, null, true],
        ];
    }
}
