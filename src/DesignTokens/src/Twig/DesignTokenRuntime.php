<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Twig;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\DesignTokens\CacheWarmer\StylesheetCache;
use Symfony\UX\DesignTokens\Exception\LogicException;
use Symfony\UX\DesignTokens\Exception\RuntimeException;
use Symfony\UX\DesignTokens\Exception\TokenNotFoundException;
use Symfony\UX\DesignTokens\Generator\ColorScheme;
use Symfony\UX\DesignTokens\Generator\CssGenerator;
use Symfony\UX\DesignTokens\Generator\GeneratorInterface;
use Symfony\UX\DesignTokens\Token\TokenInterface;
use Symfony\UX\DesignTokens\TokenRegistryInterface;
use Symfony\UX\DesignTokens\UXDesignTokensBundle;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class DesignTokenRuntime implements RuntimeExtensionInterface, ResetInterface
{
    /** The stylesheet already looked up in this request; false before the first lookup. */
    private string|false|null $stylesheetContents = false;

    public function __construct(
        private readonly TokenRegistryInterface $tokenRegistry,
        private readonly ColorScheme $colorScheme = new ColorScheme(),
        private readonly ?StylesheetCache $stylesheets = null,
        private readonly ?AssetMapperInterface $assetMapper = null,
        private readonly CssGenerator $css = new CssGenerator('dt'),
    ) {
    }

    public function renderStylesheet(): string
    {
        if (null === $this->assetMapper) {
            throw new LogicException('Serving design tokens as a stylesheet requires the AssetMapper component. Try running "composer require symfony/asset-mapper", or use ux_token_css() to inline the CSS instead.');
        }

        $this->stylesheets?->path();

        $logicalPath = UXDesignTokensBundle::ASSET_NAMESPACE.'/'.StylesheetCache::STYLESHEET;
        $publicPath = $this->assetMapper->getPublicPath($logicalPath);

        if (null === $publicPath) {
            throw new RuntimeException(\sprintf('No design token stylesheet was found at "%s".', $logicalPath));
        }

        return \sprintf('<link rel="stylesheet" href="%s">', htmlspecialchars($publicPath, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'));
    }

    /**
     * @param array<string, string|int|float> $inputs
     *
     * @throws TokenNotFoundException when the path is not found
     */
    public function getToken(string $path, array $inputs = []): TokenInterface
    {
        return $this->tokenRegistry->get($path, $inputs);
    }

    /** @param array<string, string|int|float> $inputs */
    public function renderCss(array $inputs = []): string
    {
        $css = str_replace('<', '\\3C ', $this->css($inputs));

        return '<style>'."\n".$css."\n".'</style>';
    }

    public function reset(): void
    {
        $this->stylesheetContents = false;
    }

    /**
     * An inline tag never writes to the build directory, which may be read-only.
     *
     * @param array<string, string|int|float> $inputs
     */
    private function css(array $inputs): string
    {
        if ([] === $inputs) {
            if (false === $this->stylesheetContents) {
                $this->stylesheetContents = $this->stylesheets?->read();
            }
            if (null !== $this->stylesheetContents) {
                return $this->stylesheetContents;
            }
        }

        $contexts = $this->colorScheme->contexts($this->tokenRegistry->getModifiers(), $inputs);
        if (null === $contexts) {
            return $this->css->generate($this->tokenRegistry->all($inputs));
        }

        return $this->css->generate($this->tokenRegistry->all($contexts[0]), [
            GeneratorInterface::DARK_TOKENS => $this->tokenRegistry->all($contexts[1]),
        ]);
    }
}
