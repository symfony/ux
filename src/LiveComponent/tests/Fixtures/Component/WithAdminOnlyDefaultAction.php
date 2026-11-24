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
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('with_admin_only_default_action')]
final class WithAdminOnlyDefaultAction
{
    #[LiveProp]
    public int $noteId = 42;

    public function getNote(): string
    {
        return \sprintf('note %d: admin-only-default-action-secret', $this->noteId);
    }

    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(): void
    {
    }
}
