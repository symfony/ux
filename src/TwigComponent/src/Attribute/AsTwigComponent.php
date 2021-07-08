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
    private string $name;
    private ?string $template;

    public function __construct(string $name, ?string $template = null)
    {
        $this->name = $name;
        $this->template = $template;
    }

    final public function getName(): string
    {
        return $this->name;
    }

    final public function getTemplate(): string
    {
        return $this->template ?? "components/{$this->name}.html.twig";
    }

    /**
     * @internal
     *
     * @return static
     */
    final public static function forClass(string $class): self
    {
        $class = new \ReflectionClass($class);

        if (!$attribute = $class->getAttributes(static::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a Twig Component, did you forget to add the "%s" attribute?', $class->getName(), static::class));
        }

        return $attribute->newInstance();
    }
}
