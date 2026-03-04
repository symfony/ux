<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components;

use App\Repository\ExampleRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class LiveExamplesSearch
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public string $query = '';

    public function __construct(
        private ExampleRepository $exampleRepository,
    ) {
    }

    public function getExamplesGroupedByPackage(): iterable
    {
        return $this->exampleRepository->findAllGroupedByPackage($this->query);
    }

    #[LiveAction]
    public function clearQuery(): void
    {
        $this->query = '';
    }
}
