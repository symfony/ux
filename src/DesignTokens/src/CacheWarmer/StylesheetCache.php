<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\CacheWarmer;

use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\ReflectionClassResource;
use Symfony\Component\Config\Resource\SelfCheckingResourceChecker;
use Symfony\Component\Config\ResourceCheckerConfigCache;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\DesignTokens\Generator\ColorScheme;
use Symfony\UX\DesignTokens\Generator\CssGenerator;
use Symfony\UX\DesignTokens\Generator\GeneratorInterface;
use Symfony\UX\DesignTokens\Resolver\TokenResolverInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class StylesheetCache
{
    public const DIRECTORY = 'ux_design_tokens';

    public const STYLESHEET = 'tokens.css';

    /**
     * @param array<string, string|int|float> $defaultInputs
     * @param string                          $fingerprint   identifies the configuration the stylesheet depends on
     */
    public function __construct(
        private readonly TokenResolverInterface $resolver,
        private readonly CssGenerator $css,
        private readonly string $buildDir,
        private readonly array $defaultInputs = [],
        private readonly ColorScheme $colorScheme = new ColorScheme(),
        private readonly bool $debug = false,
        private readonly string $fingerprint = '',
    ) {
    }

    public static function directory(string $buildDir): string
    {
        return $buildDir.'/'.self::DIRECTORY;
    }

    /** Path of the up-to-date stylesheet, written when missing or stale. */
    public function path(): string
    {
        $cache = $this->cache();
        if (!$cache->isFresh()) {
            [$css, $documents] = $this->render();
            $resources = [new ConfigurationResource($this->fingerprint), new ReflectionClassResource(new \ReflectionClass($this->css))];
            foreach ($documents as $document) {
                if (is_file($document)) {
                    $resources[] = new FileResource($document);
                }
            }
            $cache->write($css, $resources);
        }

        return $cache->getPath();
    }

    /** The stylesheet contents when up to date, without writing anything. */
    public function read(): ?string
    {
        $cache = $this->cache();
        if (!$cache->isFresh()) {
            return null;
        }

        try {
            return new Filesystem()->readFile($cache->getPath());
        } catch (IOExceptionInterface) {
            return null;
        }
    }

    private function cache(): ConfigCacheInterface
    {
        $checkers = $this->debug ? [new SelfCheckingResourceChecker(), new ConfigurationResource($this->fingerprint)] : [];

        return new ResourceCheckerConfigCache(self::directory($this->buildDir).'/'.self::STYLESHEET, $checkers);
    }

    /** @return array{string, list<string>} */
    private function render(): array
    {
        $contexts = $this->colorScheme->contexts($this->resolver->getModifiers(), $this->defaultInputs);
        if (null === $contexts) {
            $resolution = $this->resolver->resolve($this->defaultInputs);

            return [$this->css->generate($resolution->getTokens()), $resolution->getDocuments()];
        }

        $light = $this->resolver->resolve($contexts[0]);
        $dark = $this->resolver->resolve($contexts[1]);

        return [
            $this->css->generate($light->getTokens(), [GeneratorInterface::DARK_TOKENS => $dark->getTokens()]),
            array_values(array_unique([...$light->getDocuments(), ...$dark->getDocuments()])),
        ];
    }
}
