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

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * An application-supplied function, registered through the
 * `ux_breadcrumb.expression_function_provider` tag.
 */
final class FilterStateExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                'filter_state',
                static fn (?string $state): string => \sprintf('(%s)', $state ?? 'null'),
                static fn (array $arguments, ?string $state): ?string => null !== $state ? strtoupper($state) : null,
            ),
        ];
    }
}
