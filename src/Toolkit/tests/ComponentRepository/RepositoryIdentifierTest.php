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
use Symfony\UX\Toolkit\ComponentRepository\RepositoryIdentifier;
use Symfony\UX\Toolkit\ComponentRepository\RepositoryIdentity;
use Symfony\UX\Toolkit\ComponentRepository\RepositorySources;

/**
 * @author Jean-François Lépine
 */
class RepositoryIdentifierTest extends TestCase
{
    public function testItShouldIdentifyOfficialComponent(): void
    {
        $identifier = new RepositoryIdentifier();
        $identity = $identifier->identify('default');

        $this->assertInstanceOf(RepositoryIdentity::class, $identity);
        $this->assertEquals(RepositorySources::EMBEDDED, $identity->getType());
        $this->assertEquals('symfony', $identity->getVendor());
    }

    public function testItShouldIdentifyGithubComponent(): void
    {
        $identifier = new RepositoryIdentifier();
        $identity = $identifier->identify('https://github.com/Halleck45/uikit');

        $this->assertEquals(RepositorySources::GITHUB, $identity->getType());
        $this->assertEquals('Halleck45', $identity->getVendor());
        $this->assertEquals('uikit', $identity->getPackage());
    }

    public function testItShouldIdentifyGithubComponentWithSpecialChars(): void
    {
        $identifier = new RepositoryIdentifier();
        $identity = $identifier->identify('https://github.com/Halleck45/ui-kit2');

        $this->assertEquals(RepositorySources::GITHUB, $identity->getType());
        $this->assertEquals('Halleck45', $identity->getVendor());
        $this->assertEquals('ui-kit2', $identity->getPackage());
    }

    public function testItShouldIdentifiyGithubComponentEventWithoutScheme(): void
    {
        $identifier = new RepositoryIdentifier();
        $identity = $identifier->identify('github.com/Halleck45/uikit');

        $this->assertEquals(RepositorySources::GITHUB, $identity->getType());
        $this->assertEquals('Halleck45', $identity->getVendor());
        $this->assertEquals('uikit', $identity->getPackage());
        $this->assertEquals('main', $identity->getVersion());
    }

    public function testItShouldIdentifyGithubComponentWithVersion(): void
    {
        $identifier = new RepositoryIdentifier();
        $identity = $identifier->identify('github.com/Halleck45/uikit@v1.0.0');

        $this->assertEquals(RepositorySources::GITHUB, $identity->getType());
        $this->assertEquals('Halleck45', $identity->getVendor());
        $this->assertEquals('uikit', $identity->getPackage());
        $this->assertEquals('v1.0.0', $identity->getVersion());
    }
}
