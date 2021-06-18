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

use Symfony\UX\LiveComponent\LiveComponentInterface;

/**
 * @Annotation
 * @Target("PROPERTY")
 *
 * @experimental
 */
final class LiveProp
{
    /** @var bool */
    private $writable = false;

    /** @var string[] */
    private $exposed = [];

    /** @var string|null */
    private $hydrateWith = null;

    /** @var string|null */
    private $dehydrateWith = null;

    /**
     *The "frontend" field name that should be used for this property.
     *
     * This can be used, for example, to have a property called "foo", which actually
     * maps to a frontend data model called "bar".
     *
     * If you pass a string that ends in () - like "getFieldName()" - that
     * method on the component will be called to determine this.
     *
     * @var string|null
     */
    private $fieldName = null;

    public function __construct(array $values)
    {
        $validOptions = ['writable', 'exposed', 'hydrateWith', 'dehydrateWith', 'fieldName'];

        foreach ($values as $name => $value) {
            if (!\in_array($name, $validOptions)) {
                throw new \InvalidArgumentException(sprintf('Unknown option "%s" passed to LiveProp. Valid options are: %s.', $name, implode(', ', $validOptions)));
            }

            $this->$name = $value;
        }
    }

    public function isReadonly(): bool
    {
        return !$this->writable;
    }

    public function exposed(): array
    {
        return $this->exposed;
    }

    public function hydrateMethod(): ?string
    {
        return $this->hydrateWith ? trim($this->hydrateWith, '()') : null;
    }

    public function dehydrateMethod(): ?string
    {
        return $this->dehydrateWith ? trim($this->dehydrateWith, '()') : null;
    }

    public function calculateFieldName(LiveComponentInterface $component, string $fallback): string
    {
        if (!$this->fieldName) {
            return $fallback;
        }

        if (str_ends_with($this->fieldName, '()')) {
            return $component->{trim($this->fieldName, '()')}();
        }

        return $this->fieldName;
    }
}
