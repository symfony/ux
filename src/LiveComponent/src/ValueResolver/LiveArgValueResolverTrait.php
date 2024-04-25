<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\ValueResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Util\LiveRequestDataParser;

/**
 * @author Jannes Drijkoningen <jannesdrijkoningen@gmail.com>
 *
 * @internal
 *
 * @deprecated should be moved to LiveArgValueResolver when support for Symfony 6.1 is dropped.
 */
trait LiveArgValueResolverTrait
{
    public function supports(ArgumentMetadata $argument): bool
    {
        /** @var LiveArg|null $options */
        $options = $argument->getAttributes(LiveArg::class, ArgumentMetadata::IS_INSTANCEOF)[0] ?? null;
        if (null === $options) {
            return false;
        }

        return !property_exists($options, 'disabled') || !$options->disabled;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        /** @var LiveArg $options */
        $options = $argument->getAttributes(LiveArg::class, ArgumentMetadata::IS_INSTANCEOF)[0];

        $values = $request->attributes->get(
            '_component_action_args',
            LiveRequestDataParser::parseDataFor($request)['args']
        );

        $argumentName = $options->name ?? $argument->getName();
        $value = $values[$argumentName] ?? null;

        if ('' === $value && $argument->isNullable() && !str_contains($argument->getType(), 'string')) {
            $value = null;
        }

        return [$value];
    }
}
