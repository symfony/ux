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
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 *
 * @experimental
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class LiveFileArg
{
    public function __construct(
        public ?string $name = null,
        public bool $multiple = false
    ) {
    }

    public function getPropertyPath(): string
    {
        return preg_replace('/^([^[]+)/', '[$1]', $this->name);
    }

    /**
     * @internal
     *
     * @return array<string, self>
     */
    public static function liveFileArgs(object $component, string $action): array
    {
        $method = new \ReflectionMethod($component, $action);
        $liveFileArgs = [];

        foreach ($method->getParameters() as $parameter) {
            foreach ($parameter->getAttributes(self::class) as $liveArg) {
                /** @var LiveFileArg $attr */
                $attr = $liveArg->newInstance();
                $parameterName = $parameter->getName();

                $attr->name ??= $parameterName;

                $liveFileArgs[$parameterName] = $attr;
            }
        }

        return $liveFileArgs;
    }
}
