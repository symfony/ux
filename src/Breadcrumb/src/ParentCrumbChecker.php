<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb;

use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\Exception\LogicException;

/**
 * Only registered in debug mode.
 *
 * @internal
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class ParentCrumbChecker
{
    /**
     * A bare placeholder falls back to the route parameter of the same name, so it only needs one of the two.
     *
     * @param string               $owner           the controller class or action that declares the ancestor
     * @param string               $action          the current action, or the name of the closure controller
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $routeParameters
     */
    public function check(Breadcrumb $ancestor, string $owner, string $action, array $arguments, array $routeParameters): void
    {
        if (false === $ancestor->translationDomain) {
            return;
        }

        foreach (LabelPattern::parse($ancestor->label) as $placeholder) {
            if (null !== $argument = $placeholder['argument']) {
                if (!\array_key_exists($argument, $arguments)) {
                    throw new LogicException(\sprintf('The breadcrumb "%s" of "%s" reads the argument "$%s", which "%s" does not have.', $ancestor->label, $owner, $argument, $action));
                }

                continue;
            }

            $variable = $placeholder['variable'];
            if (!\array_key_exists($variable, $arguments) && !\array_key_exists($variable, $routeParameters)) {
                throw new LogicException(\sprintf('The breadcrumb "%s" of "%s" reads "%s", which is neither an argument of "%s" nor a parameter of its route.', $ancestor->label, $owner, $placeholder['placeholder'], $action));
            }
        }
    }
}
