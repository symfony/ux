<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Symfony\UX\StimulusBundle\Tests\fixtures\AutowiredStimulusHelperConsumer;
use Symfony\UX\StimulusBundle\Tests\StimulusIntegrationTestKernel;
use Twig\Environment;

final class StimulusHelperTest extends TestCase
{
    public function testCreateStimulusAttributes(): void
    {
        $helper = new StimulusHelper($this->createMock(Environment::class));
        $attributes = $helper->createStimulusAttributes();

        $this->assertInstanceOf(StimulusAttributes::class, $attributes);
    }

    public function testIsAutowirable(): void
    {
        $kernel = new StimulusIntegrationTestKernel();
        $kernel->boot();

        $consumer = $kernel->getContainer()->get(AutowiredStimulusHelperConsumer::class);
        $attributes = $consumer->stimulusHelper->createStimulusAttributes();
        $attributes->addController('country-picker', ['locale' => 'fr']);
        $attributes->addTarget('country-picker', 'select');
        $attributes->addAction('country-picker', 'refresh', 'change');

        // the exact attributes the documented form type example claims to produce
        $this->assertSame([
            'data-controller' => 'country-picker',
            'data-action' => 'change->country-picker#refresh',
            'data-country-picker-target' => 'select',
            'data-country-picker-locale-value' => 'fr',
        ], $attributes->toArray());
    }
}
