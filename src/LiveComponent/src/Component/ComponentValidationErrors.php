<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Component;

use Symfony\Component\Validator\ConstraintViolation;

class ComponentValidationErrors implements \Countable
{
    /**
     * @var array<string, ConstraintViolation[]>
     */
    private array $errors = [];

    public function count(): int
    {
        return \count($this->errors);
    }

    public function has(string $propertyPath): bool
    {
        return null !== $this->get($propertyPath);
    }

    public function get(string $propertyPath): ?string
    {
        $all = $this->getAll($propertyPath);

        return $all[0] ?? null;
    }

    /**
     * @return string[]
     */
    public function getAll(string $propertyPath): array
    {
        return array_map(function (ConstraintViolation $violation) {
            return $violation->getMessage();
        }, $this->errors[$propertyPath] ?? []);
    }

    /**
     * @return ConstraintViolation[]
     */
    public function getViolations(string $propertyPath): array
    {
        return $this->errors[$propertyPath] ?? [];
    }

    public function set(string $propertyName, array $constraintViolations): void
    {
        $this->errors[$propertyName] = $constraintViolations;
    }

    public function setAll(array $errors): void
    {
        $this->errors = $errors;
    }

    public function clear(): void
    {
        $this->errors = [];
    }
}
