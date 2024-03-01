<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait DefaultActionTrait
{
    /**
     * The "default" action for a component.
     *
     * This is executed when your component is being re-rendered,
     * but no custom action is being called. You probably don't
     * want to do any work here because this method is *not*
     * executed when a custom action is triggered.
     */
    public function __invoke(): void
    {
        // noop - this is the default action
    }
}
