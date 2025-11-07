<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class ComponentWithHydrateWithProps
{
    use DefaultActionTrait;

    /**
     * @var array<string, int>
     */
    #[LiveProp(writable: true, hydrateWith: 'hydrateIntegers')]
    public array $integers = [
        'one' => 1,
        'two' => 2,
        'three' => 3,
    ];

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, int>
     */
    public function hydrateIntegers(array $data): array
    {
        return array_map(intval(...), $data);
    }
}
