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

/**
 * Resolves the identity context an upload is bound to.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface UploadContextResolverInterface
{
    public function resolve(): UploadContext;
}
