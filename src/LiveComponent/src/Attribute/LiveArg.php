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

    /**
     * @internal
     *
     * @return array<string, string>
     */
    public static function liveArgs(object $component, string $action): array
    {
        $method = new \ReflectionMethod($component, $action);
        $liveArgs = [];

        foreach ($method->getParameters() as $parameter) {
            foreach ($parameter->getAttributes(self::class) as $liveArg) {
                /** @var LiveArg $attr */
                $attr = $liveArg->newInstance();
                $parameterName = $parameter->getName();

                $liveArgs[$parameterName] = $attr->name ?? $parameterName;
            }
        }

        return $liveArgs;
    }
}
