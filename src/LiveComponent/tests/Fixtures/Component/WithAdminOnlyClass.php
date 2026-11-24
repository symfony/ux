<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('with_admin_only_class')]
#[IsGranted('ROLE_ADMIN')]
final class WithAdminOnlyClass
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $noteId = 42;

    public function getNote(): string
    {
        return \sprintf('note %d: admin-only-class-secret', $this->noteId);
    }

    #[LiveAction]
    public function noop(): void
    {
    }
}
