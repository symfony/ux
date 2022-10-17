<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Twig\DeterministicTwigIdCalculator;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class DeterministicTwigIdCalculatorTest extends KernelTestCase
{
    public function testReturnsDeterministicId(): void
    {
        $deterministicIdCalculator = new DeterministicTwigIdCalculator();
        $twigExtension = new class($deterministicIdCalculator) extends AbstractExtension {
            public function __construct(private DeterministicTwigIdCalculator $deterministicIdCalculator)
            {
            }

            public function getFunctions(): array
            {
                return [
                    new TwigFunction('get_id_for_test', [$this, 'getIdForTest']),
                ];
            }

            public function getIdForTest(): string
            {
                return $this->deterministicIdCalculator->calculateDeterministicId();
            }
        };

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');
        $twig->addExtension($twigExtension);

        $rendered = $twig->render('deterministic_id.html.twig');
        $this->assertStringContainsString('Deterministic Id Line1-1: "live-3860148629-0"', $rendered);
        $this->assertStringContainsString('Deterministic Id Line1-2: "live-3860148629-1"', $rendered);
        $this->assertStringContainsString('Deterministic Id Line3: "live-136007865-0"', $rendered);

        // what if deterministic_id.html.twig is included by another file?
        $deterministicIdCalculator->reset();
        $this->assertStringContainsString('Deterministic Id Line1-1: "live-3860148629-0"', $rendered);
        $this->assertStringContainsString('Deterministic Id Line1-2: "live-3860148629-1"', $rendered);
        $this->assertStringContainsString('Deterministic Id Line3: "live-136007865-0"', $rendered);
    }
}
