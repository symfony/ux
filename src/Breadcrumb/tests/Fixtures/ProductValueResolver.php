<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Fixtures;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Stands in for Doctrine's entity resolver, so controller arguments are real objects
 * and `ControllerArgumentsEvent::getNamedArguments()` has something worth narrowing.
 */
final class ProductValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Product::class !== $argument->getType()) {
            return [];
        }

        return [new Product(slug: $request->attributes->getString('slug', 'blue-sneakers'))];
    }
}
