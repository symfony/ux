<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Twig;

use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Exception\InvalidStyleException;
use Symfony\UX\Css\Validation\StyleValidator;
use Twig\Environment;
use Twig\Node\BlockNode;
use Twig\Node\BodyNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConditionalExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\Ternary\ConditionalTernary;
use Twig\Node\Expression\Unary\NegUnary;
use Twig\Node\Expression\Unary\SpreadUnary;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\SetNode;
use Twig\Node\WithNode;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Replaces each css() call by its classes when the template compiles, and hands every style hash it resolves to a collector.
 *
 * A constant value, a ternary whose branches are constants or hashes of constants, a variable set once to a constant
 * before the call in the same body, and a nested hash of those are resolved;
 * the rest of the hash is left to {@see CssRuntime}.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class CssNodeVisitor implements NodeVisitorInterface
{
    private ?\Closure $collector = null;

    /**
     * @var list<Node> the nodes the traversal is in, outermost first
     */
    private array $ancestors = [];

    /**
     * @var \WeakMap<ModuleNode, array<string, int>> how many times each variable is assigned, by template
     */
    private \WeakMap $assignments;

    /**
     * @var \WeakMap<Node, array<string, mixed>> the constants set once in each body, by body
     */
    private \WeakMap $constants;

    /**
     * @var array<string, mixed> the constants the css() call being resolved can see
     */
    private array $visibleConstants = [];

    /**
     * @param \Closure(): Engine         $engine
     * @param \Closure(): StyleValidator $validator
     */
    public function __construct(
        private readonly \Closure $engine,
        private readonly \Closure $validator,
    ) {
        $this->assignments = new \WeakMap();
        $this->constants = new \WeakMap();
    }

    /**
     * @param (callable(array<array-key, mixed>): void)|null $collector receives every style hash resolved while templates compile
     */
    public function collectInto(?callable $collector): void
    {
        $this->collector = null === $collector ? null : $collector(...);
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            // a traversal stopped by an error leaves its nodes behind
            if ([] !== $this->ancestors && !self::isChildOf($node, end($this->ancestors))) {
                $this->ancestors = [];
            }
            $this->assignments[$node] = self::countAssignments($node);
        }
        if ($node instanceof SetNode) {
            $this->recordConstant($node);
        }
        $this->ancestors[] = $node;

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        array_pop($this->ancestors);
        if (!$node instanceof FunctionExpression || 'css' !== $node->getAttribute('name')) {
            return $node;
        }

        try {
            $this->visibleConstants = $this->constantsInScope();

            return $this->resolve($node);
        } catch (\Throwable $e) {
            throw new CssSyntaxError($e->getMessage(), $node->getTemplateLine(), $node->getSourceContext(), $e);
        }
    }

    public function getPriority(): int
    {
        return -10;
    }

    private function resolve(FunctionExpression $node): Node
    {
        $arguments = iterator_to_array($node->getNode('arguments'));
        if (\count($arguments) > 1) {
            throw new InvalidStyleException('css() takes a single hash of styles.');
        }
        $hash = reset($arguments);
        if ($hash instanceof Node && self::isHash($constant = $this->visibleConstant($hash))) {
            ($this->validator)()->validate($constant);
            $classes = new ConstantExpression($this->classNames($constant), $node->getTemplateLine());

            return new CssExpression([$classes], null, $node->getTemplateLine());
        }
        if (false !== $hash && null !== ($branches = self::constantBranches($hash))) {
            return $this->resolveTernaryOfHashes($node, ...$branches) ?? $node;
        }
        if (!$hash instanceof ArrayExpression || $hash->isSequence()) {
            return $node;
        }

        $static = [];
        $ternaries = [];
        $dynamic = new ArrayExpression([], $hash->getTemplateLine());
        if (!$this->split($hash, [], $static, $ternaries, $dynamic)) {
            return $node;
        }

        $this->validate($static, $ternaries, $dynamic);

        $parts = [];
        if ([] !== $static) {
            $parts[] = new ConstantExpression($this->classNames($static), $node->getTemplateLine());
        }
        foreach ($ternaries as [$path, $test, $left, $right]) {
            $parts[] = new ConditionalTernary(
                $test,
                new ConstantExpression($this->classNames(self::nest($path, $left)), $node->getTemplateLine()),
                new ConstantExpression($this->classNames(self::nest($path, $right)), $node->getTemplateLine()),
                $node->getTemplateLine(),
            );
        }

        $dynamicHash = [] !== iterator_to_array($dynamic) ? $dynamic : null;

        return new CssExpression($parts, $dynamicHash, $node->getTemplateLine());
    }

    private function resolveTernaryOfHashes(
        FunctionExpression $node,
        AbstractExpression $test,
        mixed $left,
        mixed $right,
    ): ?Node {
        if (!self::isHash($left) || !self::isHash($right)) {
            return null;
        }

        $validator = ($this->validator)();
        $validator->validate($left);
        $validator->validate($right);

        $line = $node->getTemplateLine();
        $ternary = new ConditionalTernary(
            $test,
            new ConstantExpression($this->classNames($left), $line),
            new ConstantExpression($this->classNames($right), $line),
            $line,
        );

        return new CssExpression([$ternary], null, $line);
    }

    /**
     * @param list<string|int>                                                $path
     * @param array<array-key, mixed>                                         $static
     * @param list<array{list<string|int>, AbstractExpression, mixed, mixed}> $ternaries
     *
     * @return bool false when a key is not a constant, which leaves the whole hash to the runtime
     */
    private function split(
        ArrayExpression $hash,
        array $path,
        array &$static,
        array &$ternaries,
        ArrayExpression $dynamic,
    ): bool {
        foreach ($hash->getKeyValuePairs() as ['key' => $keyNode, 'value' => $value]) {
            if (!$keyNode instanceof ConstantExpression || $value instanceof SpreadUnary) {
                return false;
            }
            $key = $keyNode->getAttribute('value');
            $keyPath = [...$path, $key];

            if ($value instanceof ArrayExpression && !$value->isSequence()) {
                $nested = [];
                $nestedDynamic = new ArrayExpression([], $value->getTemplateLine());
                if (!$this->split($value, $keyPath, $nested, $ternaries, $nestedDynamic)) {
                    return false;
                }
                if ([] !== $nested) {
                    $static[$key] = $nested;
                }
                if ([] !== iterator_to_array($nestedDynamic)) {
                    $dynamic->addElement($nestedDynamic, new ConstantExpression($key, $keyNode->getTemplateLine()));
                }
                continue;
            }

            if (self::isConstant($value)) {
                $static[$key] = self::constant($value);
            } elseif (null !== ($constant = $this->visibleConstant($value))) {
                $static[$key] = $constant;
            } elseif (null !== ($branches = self::constantBranches($value))) {
                $ternaries[] = [$keyPath, ...$branches];
            } else {
                $dynamic->addElement($value, new ConstantExpression($key, $keyNode->getTemplateLine()));
            }
        }

        return true;
    }

    /**
     * @param array<array-key, mixed>                                         $static
     * @param list<array{list<string|int>, AbstractExpression, mixed, mixed}> $ternaries
     */
    private function validate(array $static, array $ternaries, ArrayExpression $dynamic): void
    {
        $validator = ($this->validator)();
        $validator->validate($static);
        foreach ($ternaries as [$path, , $left, $right]) {
            $validator->validate(self::nest($path, $left));
            $validator->validate(self::nest($path, $right));
        }
        $validator->validate(self::keys($dynamic));
    }

    private function classNames(array $styles): string
    {
        if (null !== $this->collector) {
            ($this->collector)($styles);
        }

        return ($this->engine)()->classNames($styles);
    }

    private function visibleConstant(Node $node): mixed
    {
        if (!$node instanceof ContextVariable) {
            return null;
        }

        return $this->visibleConstants[$node->getAttribute('name')] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function constantsInScope(): array
    {
        $scope = self::scopeOf($this->ancestors, true);

        return null === $scope ? [] : $this->constants[$scope] ?? [];
    }

    private function recordConstant(SetNode $set): void
    {
        $names = $set->getNode('names');
        $values = $set->getNode('values');
        if ($set->getAttribute('capture') || ($names instanceof Nodes && 1 !== \count($names))) {
            return;
        }
        $target = $names instanceof Nodes ? $names->getNode('0') : $names;
        $value = $values instanceof Nodes ? $values->getNode('0') : $values;
        if (!$target instanceof AssignContextVariable || !self::isResolvable($value)) {
            return;
        }

        $name = $target->getAttribute('name');
        if ('loop' === $name || str_starts_with($name, '_')) {
            return;
        }
        $module = null;
        foreach (array_reverse($this->ancestors) as $ancestor) {
            if ($ancestor instanceof ModuleNode) {
                $module = $ancestor;
                break;
            }
        }
        $scope = self::scopeOf($this->ancestors, false);
        if (null === $module || null === $scope || 1 !== (($this->assignments[$module] ?? [])[$name] ?? 0)) {
            return;
        }

        try {
            $constants = $this->constants[$scope] ?? [];
            $constants[$name] = self::resolvedValue($value);
            $this->constants[$scope] = $constants;
        } catch (InvalidStyleException) {
            // a value that cannot be resolved leaves the variable to the runtime
        }
    }

    /**
     * @param list<Node> $ancestors
     *
     * @return Node|null the body of the template, block or macro the ancestors lead to, or null when a node that
     *                   may not run in order, like an if or a for, stands in between and $throughAnyNode is false
     */
    private static function scopeOf(array $ancestors, bool $throughAnyNode): ?Node
    {
        foreach (array_reverse($ancestors) as $ancestor) {
            if ($ancestor instanceof BodyNode || $ancestor instanceof BlockNode) {
                return $ancestor;
            }
            if ($ancestor instanceof WithNode) {
                return null;
            }
            if (!$throughAnyNode && !$ancestor instanceof Nodes && Node::class !== $ancestor::class) {
                return null;
            }
        }

        return null;
    }

    private static function isChildOf(Node $node, Node $parent): bool
    {
        foreach ($parent as $child) {
            if ($child === $node) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, int> $counts
     *
     * @return array<string, int>
     */
    private static function countAssignments(Node $node, array $counts = []): array
    {
        foreach ($node as $child) {
            if ($child instanceof AssignContextVariable) {
                $name = $child->getAttribute('name');
                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
            $counts = self::countAssignments($child, $counts);
        }

        return $counts;
    }

    /**
     * @return array{AbstractExpression, mixed, mixed}|null the test and both branches of a ternary, constants or hashes of constants
     */
    private static function constantBranches(Node $node): ?array
    {
        if ($node instanceof ConditionalTernary) {
            [$test, $left, $right] = [$node->getNode('test'), $node->getNode('left'), $node->getNode('right')];
        } elseif (class_exists(ConditionalExpression::class) && $node instanceof ConditionalExpression) {
            [$test, $left, $right] = [$node->getNode('expr1'), $node->getNode('expr2'), $node->getNode('expr3')];
        } else {
            return null;
        }

        if (!$test instanceof AbstractExpression || !self::isResolvable($left) || !self::isResolvable($right)) {
            return null;
        }

        return [$test, self::resolvedValue($left), self::resolvedValue($right)];
    }

    private static function isResolvable(Node $node): bool
    {
        return self::isConstant($node) || self::isConstantHash($node);
    }

    private static function resolvedValue(Node $node): mixed
    {
        if ($node instanceof ArrayExpression && self::isConstantHash($node)) {
            return self::constantHash($node);
        }

        return self::constant($node);
    }

    private static function isConstantHash(Node $node): bool
    {
        if (!$node instanceof ArrayExpression || $node->isSequence()) {
            return false;
        }

        foreach ($node->getKeyValuePairs() as ['key' => $key, 'value' => $value]) {
            if (!$key instanceof ConstantExpression || !self::isResolvable($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function constantHash(ArrayExpression $node): array
    {
        $hash = [];
        foreach ($node->getKeyValuePairs() as ['key' => $key, 'value' => $value]) {
            $hash[$key->getAttribute('value')] = self::resolvedValue($value);
        }

        return $hash;
    }

    private static function isHash(mixed $value): bool
    {
        return \is_array($value) && ([] === $value || !array_is_list($value));
    }

    private static function isConstant(Node $node): bool
    {
        if ($node instanceof NegUnary) {
            $node = $node->getNode('node');
        }
        if ($node instanceof ConstantExpression) {
            return true;
        }

        if (!$node instanceof ArrayExpression || !$node->isSequence()) {
            return false;
        }

        $values = array_column($node->getKeyValuePairs(), 'value');

        return [] === array_filter($values, static fn (Node $value): bool => !self::isConstant($value));
    }

    private static function constant(Node $node): mixed
    {
        if ($node instanceof NegUnary) {
            $value = self::constant($node->getNode('node'));
            if (!\is_int($value) && !\is_float($value)) {
                $message = 'Only a number can be negated. Write a negative token as a string, like "-md".';

                throw new InvalidStyleException($message);
            }

            return -$value;
        }
        if ($node instanceof ArrayExpression) {
            return array_map(self::constant(...), array_column($node->getKeyValuePairs(), 'value'));
        }

        return $node->getAttribute('value');
    }

    /**
     * @return array<array-key, mixed> the keys of a hash, with null for its values: only the keys are known when the template compiles
     */
    private static function keys(ArrayExpression $hash): array
    {
        $keys = [];
        foreach ($hash->getKeyValuePairs() as ['key' => $key, 'value' => $value]) {
            $isHash = $value instanceof ArrayExpression && !$value->isSequence();
            $keys[$key->getAttribute('value')] = $isHash ? self::keys($value) : null;
        }

        return $keys;
    }

    /**
     * @param list<string|int> $path
     *
     * @return array<array-key, mixed>
     */
    private static function nest(array $path, mixed $value): array
    {
        foreach (array_reverse($path) as $key) {
            $value = [$key => $value];
        }

        return $value;
    }
}
