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

use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\HubInterface;
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
    private readonly TopicResolver $topicResolver;

    public function __construct(
        IdAccessor $idAccessor,
        private readonly Environment $twig,
        private readonly string $hubName,
        private readonly ?HubInterface $hub = null,
        private readonly bool $ignoreCookieDomainErrors = false,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->topicResolver = new TopicResolver($idAccessor);
    }

    public function render(string|object|array $topics, array $options = []): string
    {
        $private = $options['private'] ?? false;

        $topics = \is_array($topics) ? array_values($topics) : [$topics];
        $topicStrings = TopicResolver::speaksProtocolV1($this->hub)
            ? $this->topicResolver->resolveForProtocolV1($topics)
            : $this->topicResolver->resolveForProtocolV0($topics);

        // Mercure >= 0.7: https://github.com/symfony/mercure/pull/123
        /* @phpstan-ignore-next-line function.alreadyNarrowedType */
        $mercure = is_subclass_of(MercureExtension::class, AbstractExtension::class)
            ? $this->twig->getExtension(MercureExtension::class) /* @phpstan-ignore argument.templateType */
            : $this->twig->getRuntime(MercureExtension::class);

        // Matcher-typed topics, for the protocol 1.0: symfony/mercure 0.8+
        /* @phpstan-ignore-next-line argument.type */
        $url = $mercure->mercure($topicStrings, ['hub' => $this->hubName]);

        if ($private) {
            try {
                // Matcher-typed grants, for the protocol 1.0: symfony/mercure 0.8+
                /* @phpstan-ignore-next-line argument.type */
                $mercure->mercure(null, ['hub' => $this->hubName, 'subscribe' => $topicStrings]);
            } catch (RuntimeException $e) {
                if (!$this->ignoreCookieDomainErrors || !str_starts_with($e->getMessage(), 'Unable to create authorization cookie for a hub on the different second-level domain')) {
                    throw $e;
                }

                $this->logger?->warning('The private Turbo stream source of the "{hub}" Mercure hub is not rendered, as its authorization cookie cannot be created for the domain of the page: {message}', [
                    'hub' => $this->hubName,
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]);

                return '';
            }
        }

        return \sprintf(
            '<turbo-mercure-stream-source src="%s"%s></turbo-mercure-stream-source>',
            htmlspecialchars($url, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
            $private ? ' private' : '',
        );
    }
}
