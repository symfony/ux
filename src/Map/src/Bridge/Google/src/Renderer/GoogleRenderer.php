<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Google\Renderer;

use Symfony\UX\Map\Bridge\Google\GoogleOptions;
use Symfony\UX\Map\Icon\UxIconRenderer;
use Symfony\UX\Map\MapOptionsInterface;
use Symfony\UX\Map\Renderer\AbstractRenderer;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class GoogleRenderer extends AbstractRenderer
{
    /**
     * Parameters are based from https://googlemaps.github.io/js-api-loader/interfaces/LoaderOptions.html documentation.
     */
    public function __construct(
        StimulusHelper $stimulusHelper,
        UxIconRenderer $uxIconRenderer,
        #[\SensitiveParameter] private readonly string $apiKey,
        private readonly ?string $id = null,
        private readonly ?string $language = null,
        private readonly ?string $region = null,
        private readonly ?string $nonce = null,
        private readonly ?int $retries = null,
        private readonly ?string $url = null,
        private readonly ?string $version = null,
        /**
         * @var array<'core'|'maps'|'places'|'geocoding'|'routes'|'marker'|'geometry'|'elevation'|'streetView'|'journeySharing'|'drawing'|'visualization'>
         */
        private readonly array $libraries = [],
        private readonly ?string $defaultMapId = null,
    ) {
        parent::__construct($stimulusHelper, $uxIconRenderer);
    }

    protected function getName(): string
    {
        return 'google';
    }

    protected function getProviderOptions(): array
    {
        return array_filter([
            'id' => $this->id,
            'language' => $this->language,
            'region' => $this->region,
            'nonce' => $this->nonce,
            'retries' => $this->retries,
            'url' => $this->url,
            'version' => $this->version,
            'libraries' => $this->libraries,
        ]) + ['apiKey' => $this->apiKey];
    }

    protected function getDefaultMapOptions(): MapOptionsInterface
    {
        return new GoogleOptions(mapId: $this->defaultMapId);
    }

    protected function tapOptions(MapOptionsInterface $options): MapOptionsInterface
    {
        if (!$options instanceof GoogleOptions) {
            throw new \InvalidArgumentException(\sprintf('The options must be an instance of "%s", got "%s" instead.', GoogleOptions::class, get_debug_type($options)));
        }

        if (!$options->hasMapId()) {
            $options->mapId($this->defaultMapId);
        }

        return $options;
    }

    public function __toString(): string
    {
        return \sprintf(
            'google://%s@default/?%s',
            str_repeat('*', \strlen($this->apiKey)),
            http_build_query(array_filter([
                'id' => $this->id,
                'language' => $this->language,
                'region' => $this->region,
                'nonce' => $this->nonce,
                'retries' => $this->retries,
                'url' => $this->url,
                'version' => $this->version,
                'libraries' => $this->libraries,
            ]))
        );
    }
}
