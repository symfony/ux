<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Twig\Node;

use Symfony\UX\Icons\IconRenderer;
use Twig\Compiler;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;

final class UXIconFunction extends FunctionExpression
{
    public function compile(Compiler $compiler): void
    {
        $arguments = $this->getNode('arguments');

        $iconName = $attributes = null;

        if ($arguments->hasNode('name')) {
            $iconName = $arguments->getNode('name');
        } elseif ($arguments->hasNode('0')) {
            $iconName = $arguments->getNode('0');
        }

        if (!$iconName instanceof ConstantExpression) {
            parent::compile($compiler);

            return;
        }

        $iconAttributes = [];

        if ($arguments->hasNode('1')) {
            $attributes = $arguments->getNode('1');
        } elseif ($arguments->hasNode('attributes')) {
            $attributes = $arguments->getNode('attributes');
        }

        if ($attributes instanceof ArrayExpression) {
            foreach ($attributes->getKeyValuePairs() as $attribute) {
                // Cannot handle dynamic attribute values
                if (!$attribute['key'] instanceof ConstantExpression || !$attribute['value'] instanceof ConstantExpression) {
                    parent::compile($compiler);

                    return;
                }

                $iconAttributes[$attribute['key']->getAttribute('value')] = $attribute['value']->getAttribute('value');
            }
        }

        $compiler->string(
            $compiler
                ->getEnvironment()
                ->getRuntime(IconRenderer::class)
                ->renderIcon($iconName->getAttribute('value'), $iconAttributes)
        );
    }
}
