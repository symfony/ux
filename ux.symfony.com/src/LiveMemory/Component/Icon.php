<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\LiveMemory\Component;

use App\Twig\Icon as BaseIcon;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @demo LiveMemory
 *
 * @author Simon André <smn.andre@gmail.com>
 */
#[AsTwigComponent(
    name: 'LiveMemory:Icon',
    template: 'demos/live_memory/components/LiveMemory/Icon.html.twig',
)]
class Icon extends BaseIcon
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ) {
        $this->iconDirectory = $projectDir.'/assets/images/demos/live-memory/icons/';
    }
}
