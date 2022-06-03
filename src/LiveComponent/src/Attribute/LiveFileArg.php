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
    private ?array $types = null;

    public function __construct(public ?string $name = null)
    {
    }

    public function isValueCompatible(mixed $value): bool
    {
        if (null === $this->types) {
            return true;
        }
        $type = \gettype($value);
        if ('object' === $type) {
            $type = $value::class;
        }

        return \in_array($type, $this->types, true);
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
                if ($type = $parameter->getType()) {
                    if ($type instanceof \ReflectionNamedType) {
                        $attr->types = [$type->getName()];
                    } else {
                        $attr->types = array_map(
                            static fn (\ReflectionNamedType $type) => $type->getName(),
                            $type->getTypes()
                        );
                    }
                }

                $liveFileArgs[$parameterName] = $attr;
            }
        }

        return $liveFileArgs;
    }
}
