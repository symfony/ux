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
        private string $name,
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
        $methods = iterator_to_array(self::attributeMethodsFor(PreMount::class, $component));

        usort($methods, static function (\ReflectionMethod $a, \ReflectionMethod $b) {
            return $a->getAttributes(PreMount::class)[0]->newInstance()->priority <=> $b->getAttributes(PreMount::class)[0]->newInstance()->priority;
        });

        return array_reverse($methods);
    }

    /**
     * @internal
     *
     * @return \ReflectionMethod[]
     */
    public static function postMountMethods(object $component): iterable
    {
        $methods = iterator_to_array(self::attributeMethodsFor(PostMount::class, $component));

        usort($methods, static function (\ReflectionMethod $a, \ReflectionMethod $b) {
            return $a->getAttributes(PostMount::class)[0]->newInstance()->priority <=> $b->getAttributes(PostMount::class)[0]->newInstance()->priority;
        });

        return array_reverse($methods);
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
