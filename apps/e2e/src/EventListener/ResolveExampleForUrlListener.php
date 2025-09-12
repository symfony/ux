<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventListener;

use App\Repository\ExampleRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener]
class ResolveExampleForUrlListener
{
    public function __construct(
        private ExampleRepository $exampleRepository
    )
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $example = $this->exampleRepository->findOneByUrl($event->getRequest()->getRequestUri());
        $event->getRequest()->attributes->set('_example', $example);
    }
}
