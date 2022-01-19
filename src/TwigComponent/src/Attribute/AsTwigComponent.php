<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Attribute;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsTwigComponent
{
    public string $name;
    public ?string $template;

    public function __construct(string $name, ?string $template = null)
    {
        $this->name = $name;
        $this->template = $template;
    }

    /**
     * @internal
     *
     * @return \ReflectionMethod[]
     */
    public static function preMountMethods(object $component): \Traversable
    {
        yield from self::attributeMethodsFor(PreMount::class, $component);
    }

    /**
     * @internal
     *
     * @return \ReflectionMethod[]
     */
    protected static function attributeMethodsFor(string $attribute, object $component): \Traversable
    {
        foreach ((new \ReflectionClass($component))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getAttributes($attribute)[0] ?? null) {
                yield $method;
            }
        }
    }
}
