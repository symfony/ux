<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class TwigExtension extends AbstractExtension
{
    private const REFRESH_METHOD_REPLACE = 'replace';
    private const REFRESH_METHOD_MORPH = 'morph';

    private const REFRESH_SCROLL_RESET = 'reset';
    private const REFRESH_SCROLL_PRESERVE = 'preserve';

    public function getFunctions(): array
    {
        return [
            new TwigFunction('turbo_stream_listen', [TurboRuntime::class, 'renderTurboStreamListen'], ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('turbo_exempts_page_from_cache', $this->turboExemptsPageFromCache(...), ['is_safe' => ['html']]),
            new TwigFunction('turbo_exempts_page_from_preview', $this->turboExemptsPageFromPreview(...), ['is_safe' => ['html']]),
            new TwigFunction('turbo_page_requires_reload', $this->turboPageRequiresReload(...), ['is_safe' => ['html']]),
            new TwigFunction('turbo_refreshes_with', $this->turboRefreshesWith(...), ['is_safe' => ['html']]),
            new TwigFunction('turbo_refresh_method', $this->turboRefreshMethod(...), ['is_safe' => ['html']]),
            new TwigFunction('turbo_refresh_scroll', $this->turboRefreshScroll(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * Generates a <meta> tag to disable caching of a page.
     *
     *  Inspired by Turbo Rails
     *  ({@see https://github.com/hotwired/turbo-rails/blob/main/app/helpers/turbo/drive_helper.rb}).
     */
    public function turboExemptsPageFromCache(): string
    {
        return '<meta name="turbo-cache-control" content="no-cache">';
    }

    /**
     * Generates a <meta> tag to specify cached version of the page should not be shown as a preview on regular navigation visits.
     *
     *  Inspired by Turbo Rails
     *  ({@see https://github.com/hotwired/turbo-rails/blob/main/app/helpers/turbo/drive_helper.rb}).
     */
    public function turboExemptsPageFromPreview(): string
    {
        return '<meta name="turbo-cache-control" content="no-preview">';
    }

    /**
     * Generates a <meta> tag to force a full page reload.
     *
     *  Inspired by Turbo Rails
     *  ({@see https://github.com/hotwired/turbo-rails/blob/main/app/helpers/turbo/drive_helper.rb}).
     */
    public function turboPageRequiresReload(): string
    {
        return '<meta name="turbo-visit-control" content="reload">';
    }

    /**
     * Generates <meta> tags to configure both the refresh method and scroll behavior for page refreshes.
     *
     * Inspired by Turbo Rails
     * ({@see https://github.com/hotwired/turbo-rails/blob/main/app/helpers/turbo/drive_helper.rb}).
     *
     * @param string $method The refresh method. Must be either 'replace' or 'morph'.
     * @param string $scroll The scroll behavior. Must be either 'reset' or 'preserve'.
     *
     * @return string The <meta> tags for the specified refresh method and scroll behavior
     */
    public function turboRefreshesWith(string $method = self::REFRESH_METHOD_REPLACE, string $scroll = self::REFRESH_SCROLL_RESET): string
    {
        return $this->turboRefreshMethod($method).$this->turboRefreshScroll($scroll);
    }

    /**
     * Generates a <meta> tag to configure the refresh method for page refreshes.
     *
     * Inspired by Turbo Rails
     * ({@see https://github.com/hotwired/turbo-rails/blob/main/app/helpers/turbo/drive_helper.rb}).
     *
     * @param string $method The refresh method. Must be either 'replace' or 'morph'.
     *
     * @return string The <meta> tag for the specified refresh method
     *
     * @throws \InvalidArgumentException If an invalid refresh method is provided
     */
    public function turboRefreshMethod(string $method = self::REFRESH_METHOD_REPLACE): string
    {
        if (!\in_array($method, [self::REFRESH_METHOD_REPLACE, self::REFRESH_METHOD_MORPH], true)) {
            throw new \InvalidArgumentException(\sprintf('Invalid refresh option "%s".', $method));
        }

        return \sprintf('<meta name="turbo-refresh-method" content="%s">', $method);
    }

    /**
     * Generates a <meta> tag to configure the scroll behavior for page refreshes.
     *
     * Inspired by Turbo Rails
     * ({@see https://github.com/hotwired/turbo-rails/blob/main/app/helpers/turbo/drive_helper.rb}).
     *
     * @param string $scroll The scroll behavior. Must be either 'reset' or 'preserve'.
     *
     * @return string The <meta> tag for the specified scroll behavior
     *
     * @throws \InvalidArgumentException If an invalid scroll behavior is provided
     */
    public function turboRefreshScroll(string $scroll = self::REFRESH_SCROLL_RESET): string
    {
        if (!\in_array($scroll, [self::REFRESH_SCROLL_RESET, self::REFRESH_SCROLL_PRESERVE], true)) {
            throw new \InvalidArgumentException(\sprintf('Invalid scroll option "%s".', $scroll));
        }

        return \sprintf('<meta name="turbo-refresh-scroll" content="%s">', $scroll);
    }
}
