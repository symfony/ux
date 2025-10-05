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
     * Parameters are based from https://github.com/googlemaps/js-api-loader/blob/107cf47/src/index.ts#L28-L38 APIOptions interface.
     */
    public function __construct(
        StimulusHelper $stimulusHelper,
        UxIconRenderer $uxIconRenderer,
        #[\SensitiveParameter] private readonly string $key,
        private readonly ?string $v = null,
        private readonly ?string $language = null,
        private readonly ?string $region = null,
        /**
         * @var array<'core'|'maps'|'places'|'geocoding'|'routes'|'marker'|'geometry'|'elevation'|'streetView'|'journeySharing'|'drawing'|'visualization'>
         */
        private readonly array $libraries = [],
        private readonly ?string $authReferrerPolicy = null,
        private readonly array $mapIds = [],
        private readonly ?string $channel = null,
        private readonly ?string $solutionChannel = null,
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
            'v' => $this->v,
            'language' => $this->language,
            'region' => $this->region,
            'libraries' => $this->libraries,
            'authReferrerPolicy' => $this->authReferrerPolicy,
            'mapIds' => $this->defaultMapId && !\in_array($this->defaultMapId, $this->mapIds, true) ? [...$this->mapIds, $this->defaultMapId] : $this->mapIds,
            'channel' => $this->channel,
            'solutionChannel' => $this->solutionChannel,
        ]) + ['key' => $this->key];
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
            str_repeat('*', \strlen($this->key)),
            http_build_query(array_filter([
                'v' => $this->v,
                'language' => $this->language,
                'region' => $this->region,
                'libraries' => $this->libraries,
                'authReferrerPolicy' => $this->authReferrerPolicy,
                'mapIds' => $this->mapIds,
                'channel' => $this->channel,
                'solutionChannel' => $this->solutionChannel,
            ]))
        );
    }
}
