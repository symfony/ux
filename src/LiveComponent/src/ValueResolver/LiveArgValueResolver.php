<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\ValueResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

if (interface_exists(ValueResolverInterface::class)) {
    /**
     * @author Jannes Drijkoningen <jannesdrijkoningen@gmail.com>
     *
     * @internal
     */
    class LiveArgValueResolver implements ValueResolverInterface
    {
        use LiveArgValueResolverTrait {
            resolve as resolveArgument;
        }

        public function resolve(Request $request, ArgumentMetadata $argument): iterable
        {
            if (!$this->supports($argument)) {
                return [];
            }

            return $this->resolveArgument($request, $argument);
        }
    }
} else {
    /**
     * @author Jannes Drijkoningen <jannesdrijkoningen@gmail.com>
     *
     * @internal
     *
     * @deprecated should be removed when Symfony 6.1 is no longer supported
     */
    class LiveArgValueResolver implements ArgumentValueResolverInterface
    {
        use LiveArgValueResolverTrait {
            supports as supportsArgument;
        }

        public function supports(Request $request, ArgumentMetadata $argument): bool
        {
            return $this->supportsArgument($argument);
        }
    }
}
