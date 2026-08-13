<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class SymfonySecurityUploadContextResolver implements UploadContextResolverInterface
{
    public function __construct(private ?Security $security)
    {
    }

    public function resolve(): UploadContext
    {
        $user = $this->security?->getUser();

        return new UploadContext(
            ownerId: $user instanceof UserInterface ? $user->getUserIdentifier() : null,
        );
    }
}
