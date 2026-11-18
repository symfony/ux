<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

final class TargetProvider
{
    /**
     * @return list<Target>
     */
    public function targets(): array
    {
        return [
            new Target('css/tailwind', 'Css', '/css/tailwind', minimumCounts: ['rows' => 25]),
            new Target('css/ux-css', 'Css', '/css/ux-css', minimumCounts: ['rows' => 25]),
            new Target('twig-component/dashboard', 'TwigComponent', '/twig-component/dashboard', minimumCounts: ['components' => 3000, 'rows' => 500]),
            new Target('live/dashboard', 'LiveComponent', '/live/dashboard', minimumCounts: ['rows' => 100, 'live_children' => 100]),
            new Target('live/props', 'LiveComponent', '/live/props'),
            $this->live('live/table-search', 'Live:PostsTable', updated: ['query' => 'symfony']),
            $this->live('live/table-select', 'Live:PostsTable', selectRows: 3),
            $this->live('live/table-bulk-delete', 'Live:PostsTable', action: 'bulkDelete', selectRows: 3),
            $this->live('live/table-next-page', 'Live:PostsTable', action: 'goToPage', args: ['page' => 2]),
            $this->live('live/form-validate', 'Live:PostForm', updated: ['post.title' => 'Too short', 'validatedFields' => ['post.title']], expectedStatus: 422),
            $this->live('live/featured-toggle', 'Live:FeaturedToggle', action: 'toggle'),
            $this->live('live/stats-refresh', 'Live:DashboardStats', action: 'refresh'),
            $this->live('live/props-update', 'Live:TonsOfLiveProp', updated: ['name' => 'Jane'], page: '/live/props'),
            new Target('icons/grid', 'Icons', '/icons/grid', minimumCounts: ['icons' => 2000]),
            new Target('map/leaflet', 'Map', '/map/leaflet', minimumCounts: ['markers' => 2000]),
            new Target('map/google', 'Map', '/map/google', minimumCounts: ['markers' => 2000]),
        ];
    }

    /**
     * @param list<string> $prefixes
     *
     * @return list<Target>
     */
    public function only(array $prefixes): array
    {
        if ([] === $prefixes) {
            return $this->targets();
        }

        $matching = [];
        foreach ($this->targets() as $target) {
            foreach ($prefixes as $prefix) {
                if ($target->matches($prefix)) {
                    $matching[] = $target;

                    break;
                }
            }
        }

        return $matching;
    }

    /**
     * @return list<string>
     */
    public function packages(): array
    {
        return array_values(array_unique(array_map(
            static fn (Target $target): string => $target->package,
            $this->targets(),
        )));
    }

    /**
     * @param array<string, mixed> $updated
     * @param array<string, mixed> $args
     */
    private function live(
        string $name,
        string $component,
        array $updated = [],
        ?string $action = null,
        array $args = [],
        ?int $selectRows = null,
        int $expectedStatus = 200,
        string $page = '/live/dashboard',
    ): Target {
        return new Target(
            $name,
            'LiveComponent',
            $page,
            $expectedStatus,
            new LiveScenario($page, $component, $updated, $action, $args, $selectRows),
        );
    }
}
