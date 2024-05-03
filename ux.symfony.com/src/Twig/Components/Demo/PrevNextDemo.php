<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Demo;

use App\Model\LiveDemo;
use App\Service\LiveDemoRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Demo:PrevNext')]
class PrevNextDemo
{
    public function __construct(
        private readonly LiveDemoRepository $demoRepository,
    ) {
    }

    public LiveDemo $demo;

    public function getPrevious(bool $loop = false): ?LiveDemo
    {
        return $this->demoRepository->getPrevious($this->demo->getIdentifier(), $loop);
    }

    public function getNext(bool $loop = false): ?LiveDemo
    {
        return $this->demoRepository->getNext($this->demo->getIdentifier(), $loop);
    }
}
