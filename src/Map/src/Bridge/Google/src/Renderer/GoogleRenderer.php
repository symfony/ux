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
use Symfony\UX\Map\MapOptionsInterface;
use Symfony\UX\Map\Renderer\AbstractRenderer;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final readonly class GoogleRenderer extends AbstractRenderer
{
    /**
     * Parameters are based from https://googlemaps.github.io/js-api-loader/interfaces/LoaderOptions.html documentation.
     */
    public function __construct(
        StimulusHelper $stimulusHelper,
        #[\SensitiveParameter]
        private string $apiKey,
        private ?string $id = null,
        private ?string $language = null,
        private ?string $region = null,
        private ?string $nonce = null,
        private ?int $retries = null,
        private ?string $url = null,
        private ?string $version = null,
    ) {
        parent::__construct($stimulusHelper);
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
        ]) + ['apiKey' => $this->apiKey];
    }

    protected function getDefaultMapOptions(): MapOptionsInterface
    {
        return new GoogleOptions();
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
            ]))
        );
    }
}
