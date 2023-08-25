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
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsTwigComponent
{
    public function __construct(
        private ?string $name = null,
        private ?string $template = null,
        private bool $exposePublicProps = true,
        private string $attributesVar = 'attributes'
    ) {
    }

    /**
     * @internal
     */
    public function serviceConfig(): array
    {
        return [
            'key' => $this->name,
            'template' => $this->template,
            'expose_public_props' => $this->exposePublicProps,
            'attributes_var' => $this->attributesVar,
        ];
    }

    /**
     * @internal
     *
     * @return \ReflectionMethod[]
     */
    public static function preMountMethods(object $component): iterable
    {
        return self::attributeMethodsByPriorityFor($component, PreMount::class);
    }

    /**
     * @internal
     *
     * @return \ReflectionMethod[]
     */
    public static function postMountMethods(object $component): iterable
    {
        return self::attributeMethodsByPriorityFor($component, PostMount::class);
    }

    /**
     * @internal
     *
     * @return \ReflectionMethod[]
     */
    protected static function attributeMethodsFor(string $attribute, object $component): \Traversable
    {
        foreach ((new \ReflectionClass($component))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getAttributes($attribute, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null) {
                yield $method;
            }
        }
    }

    /**
     * @param class-string $attributeClass
     *
     * @return \ReflectionMethod[]
     *
     * @internal
     */
    protected static function attributeMethodsByPriorityFor(object $component, string $attributeClass): array
    {
        $methods = iterator_to_array(self::attributeMethodsFor($attributeClass, $component));

        usort($methods, static function (\ReflectionMethod $a, \ReflectionMethod $b) use ($attributeClass) {
            return $a->getAttributes($attributeClass)[0]->newInstance()->priority <=> $b->getAttributes($attributeClass)[0]->newInstance()->priority;
        });

        return array_reverse($methods);
    }
}
