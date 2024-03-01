<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Entity\Food;
use App\Repository\FoodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class FoodVote extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public Food $food;

    #[LiveProp]
    public bool $hasVoted = false;

    public function __construct(private FoodRepository $foodRepository)
    {
    }

    #[LiveAction]
    public function vote(#[LiveArg] string $direction)
    {
        if ('up' === $direction) {
            $this->food->upVote();
        } else {
            $this->food->downVote();
        }

        $this->foodRepository->add($this->food, true);
        $this->hasVoted = true;
    }
}
