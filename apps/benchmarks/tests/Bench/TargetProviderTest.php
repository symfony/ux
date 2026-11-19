<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Bench;

use App\Bench\Target;
use App\Bench\TargetProvider;
use PHPUnit\Framework\TestCase;

final class TargetProviderTest extends TestCase
{
    public function testItListsSixteenTargetsInPackageOrder(): void
    {
        $provider = new TargetProvider();

        $names = array_map(static fn (Target $target): string => $target->name, $provider->targets());

        self::assertSame(['css/tailwind', 'css/ux-css', 'twig-component/dashboard', 'live/dashboard', 'live/props', 'live/table-search', 'live/table-select', 'live/table-bulk-delete', 'live/table-next-page', 'live/form-validate', 'live/featured-toggle', 'live/stats-refresh', 'live/props-update', 'icons/grid', 'map/leaflet', 'map/google'], $names);
    }

    public function testOnlyFiltersByPrefix(): void
    {
        $provider = new TargetProvider();

        $targets = $provider->only(['live/table']);

        self::assertSame(
            ['live/table-search', 'live/table-select', 'live/table-bulk-delete', 'live/table-next-page'],
            array_map(static fn (Target $target): string => $target->name, $targets),
        );
    }

    public function testOnlyWithoutPrefixesReturnsEverything(): void
    {
        $provider = new TargetProvider();

        $targets = $provider->only([]);

        self::assertCount(16, $targets);
    }

    public function testPackagesAreListedOnceInOrder(): void
    {
        $provider = new TargetProvider();

        $packages = $provider->packages();

        self::assertSame(['Css', 'TwigComponent', 'LiveComponent', 'Icons', 'Map'], $packages);
    }

    public function testFormValidateExpects422(): void
    {
        $provider = new TargetProvider();

        $targets = $provider->only(['live/form-validate']);

        self::assertCount(1, $targets);
        self::assertSame(422, $targets[0]->expectedStatus);
        self::assertTrue($targets[0]->isLive());
        self::assertSame('Live:PostForm', $targets[0]->scenario->component);
    }
}
