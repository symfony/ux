<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Attribute;

use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\UX\LiveComponent\ValueResolver\LiveArgValueResolver;

if (class_exists(ValueResolver::class)) {
    /**
     * An attribute to configure a LiveArg (custom argument passed to a LiveAction).
     *
     * @see https://symfony.com/bundles/ux-live-component/current/index.html#actions-arguments
     *
     * @author Tomas Norkūnas <norkunas.tom@gmail.com>
     * @author Jannes Drijkoningen <jannesdrijkoningen@gmail.com>
     */
    #[\Attribute(\Attribute::TARGET_PARAMETER)]
    final class LiveArg extends ValueResolver
    {
        public function __construct(
            /**
             * @param string|null $name The name of the argument received by the LiveAction
             */
            public ?string $name = null,
            bool $disabled = false,
            string $resolver = LiveArgValueResolver::class,
        ) {
            parent::__construct($resolver, $disabled);
        }
    }
} else {
    /**
     * An attribute to configure a LiveArg (custom argument passed to a LiveAction).
     *
     * @see https://symfony.com/bundles/ux-live-component/current/index.html#actions-arguments
     *
     * @author Tomas Norkūnas <norkunas.tom@gmail.com>
     */
    #[\Attribute(\Attribute::TARGET_PARAMETER)]
    final class LiveArg
    {
        public function __construct(
            /**
             * @param string|null $name The name of the argument received by the LiveAction
             */
            public ?string $name = null,
        ) {
        }
    }
}
