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

namespace Symfony\UX\Threejs\Live;

use Symfony\UX\Threejs\Three;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @experimental
 */
trait ComponentWithThreeTrait
{
    /**
     * @internal
     */
    #[LiveProp(hydrateWith: 'hydrateThree', dehydrateWith: 'dehydrateThree')]
    #[ExposeInTemplate(getter: 'getThree')]
    public ?Three $three = null;

    abstract protected function instantiateThree(): Three;

    public function getThree(): Three
    {
        return $this->three ??= $this->instantiateThree();
    }

    /**
     * @internal
     */
    #[PostMount]
    public function initializeThree(array $data): array
    {
        // allow the Three object to be passed into the component() as "three"
        if (\array_key_exists('three', $data)) {
            $this->three = $data['three'];
            unset($data['three']);
        }

        return $data;
    }

    /**
     * @internal
     */
    public function hydrateThree(array $data): Three
    {
        return Three::fromArray($data);
    }

    /**
     * @internal
     */
    public function dehydrateThree(Three $three): array
    {
        return $three->toArray();
    }
}
