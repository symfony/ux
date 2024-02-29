<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent;

use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
class ComponentValidator implements ComponentValidatorInterface, ServiceSubscriberInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @return ConstraintViolation[][]
     */
    public function validate(object $component): array
    {
        $errors = $this->getValidator()->validate($component);

        $validationErrors = [];
        foreach ($errors as $error) {
            /** @var ConstraintViolation $error */
            $property = $error->getPropertyPath();
            if (!isset($validationErrors[$property])) {
                $validationErrors[$property] = [];
            }

            $validationErrors[$property][] = $error;
        }

        return $validationErrors;
    }

    /**
     * Validates a single field.
     *
     * If a property path - like post.title - is passed, this will
     * validate the *entire* "post" property. It will then loop
     * over all the errors and collect only those for "post.title".
     *
     * @return ConstraintViolation[]
     */
    public function validateField(object $component, string $propertyPath): array
    {
        $propertyParts = explode('.', $propertyPath);
        $propertyName = $propertyParts[0];

        $errors = $this->getValidator()->validateProperty($component, $propertyName);

        $errorsForPath = [];
        foreach ($errors as $error) {
            /** @var ConstraintViolation $error */
            if ($error->getPropertyPath() === $propertyPath) {
                $errorsForPath[] = $error;
            }
        }

        return $errorsForPath;
    }

    private function getValidator(): ValidatorInterface
    {
        return $this->container->get('validator');
    }

    public static function getSubscribedServices(): array
    {
        return [
            'validator' => ValidatorInterface::class,
        ];
    }
}
