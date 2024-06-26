<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Test;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\ComponentFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait InteractsWithLiveComponents
{
    protected function createLiveComponent(string $name, array $data = [], ?KernelBrowser $client = null): TestLiveComponent
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException(\sprintf('The "%s" trait can only be used on "%s" classes.', __TRAIT__, KernelTestCase::class));
        }

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');
        $metadata = $factory->metadataFor($name);

        if (!$metadata->get('live')) {
            throw new \LogicException(\sprintf('The "%s" component is not a live component.', $name));
        }

        return new TestLiveComponent(
            $metadata,
            $data,
            $factory,
            $client ?? self::getContainer()->get('test.client'),
            self::getContainer()->get('ux.live_component.component_hydrator'),
            self::getContainer()->get('ux.live_component.metadata_factory'),
            self::getContainer()->get('router'),
        );
    }
}
