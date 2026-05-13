<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Bridge\Mercure;

use Symfony\Component\Mercure\Twig\MercureExtension;
use Symfony\UX\Turbo\Broadcaster\IdAccessor;
use Symfony\UX\Turbo\StreamSourceRendererInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;

/**
 * Renders a Mercure stream source element, delegating authorization to the Mercure Bundle.
 *
 * For private topics, sets the Mercure authorization cookie as a side effect so the
 * browser EventSource can authenticate with `withCredentials: true`.
 *
 * @author Sébastien Jean <sebastien.jean76@gmail.com>
 */
final class MercureStreamSourceRenderer implements StreamSourceRendererInterface
{
    public function __construct(
        private readonly IdAccessor $idAccessor,
        private readonly Environment $twig,
        private readonly string $hubName,
    ) {
    }

    public function render(string|object|array $topics, array $options = []): string
    {
        $private = $options['private'] ?? false;

        $topicStrings = array_map(
            $this->resolveTopic(...),
            \is_array($topics) ? $topics : [$topics],
        );

        $mercureOptions = ['hub' => $this->hubName];
        if ($private) {
            $mercureOptions['withCredentials'] = true;
            $mercureOptions['subscribe'] = $topicStrings;
        }

        // Mercure >= 0.7: https://github.com/symfony/mercure/pull/123
        /* @phpstan-ignore-next-line function.alreadyNarrowedType */
        $mercure = is_subclass_of(MercureExtension::class, AbstractExtension::class)
            ? $this->twig->getExtension(MercureExtension::class) /* @phpstan-ignore argument.templateType */
            : $this->twig->getRuntime(MercureExtension::class);

        $url = $mercure->mercure($topicStrings, $mercureOptions);

        return \sprintf(
            '<turbo-mercure-stream-source src="%s"%s></turbo-mercure-stream-source>',
            htmlspecialchars($url, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
            $private ? ' private' : '',
        );
    }

    private function resolveTopic(object|string $topic): string
    {
        if (\is_object($topic)) {
            $class = $topic::class;

            if (!$id = $this->idAccessor->getEntityId($topic)) {
                throw new \LogicException(\sprintf('Cannot listen to entity of class "%s" as the PropertyAccess component is not installed. Try running "composer require symfony/property-access".', $class));
            }

            return \sprintf(Broadcaster::TOPIC_PATTERN, rawurlencode($class), rawurlencode(implode('-', $id)));
        }

        if (!preg_match('/[^a-zA-Z0-9_\x7f-\xff\\\\]/', $topic) && class_exists($topic)) {
            return \sprintf(Broadcaster::TOPIC_PATTERN, rawurlencode($topic), '{id}');
        }

        return $topic;
    }
}
