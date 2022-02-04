<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\PreMount;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsTwigComponent('component_b', template: 'components/custom1.html.twig')]
final class ComponentB
{
    public string $value;
    public string $postValue;

    #[PreMount]
    public function preMount(array $data): array
    {
        if (isset($data['value'])) {
            $data['value'] = 'pre-mount '.$data['value'];
        }

        return $data;
    }

    #[PostMount]
    public function postMount(array $data): array
    {
        $this->postValue = $data['extra'] ?? 'default';

        return [];
    }
}
