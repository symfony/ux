<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Represents an error during hydration.
 *
 * When this is thrown, the user will see the error modal.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
class HydrationException extends BadRequestHttpException
{
}
