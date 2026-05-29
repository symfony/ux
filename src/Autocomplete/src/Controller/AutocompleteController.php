<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Autocomplete\AutocompleteResultsExecutor;
use Symfony\UX\Autocomplete\AutocompleterRegistry;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceTypeExtension;
use Symfony\UX\Autocomplete\OptionsAwareAutocompleterInterface;
use Symfony\UX\Autocomplete\OptionsAwareEntityAutocompleterInterface;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class AutocompleteController
{
    public const EXTRA_OPTIONS = 'extra_options';

    public function __construct(
        private AutocompleterRegistry $autocompleteFieldRegistry,
        private AutocompleteResultsExecutor $autocompleteResultsExecutor,
        private UrlGeneratorInterface $urlGenerator,
        private ChecksumCalculator $checksumCalculator,
    ) {
    }

    public function __invoke(string $alias, Request $request): Response
    {
        $autocompleter = $this->autocompleteFieldRegistry->getAutocompleter($alias);
        if (null === $autocompleter) {
            throw new NotFoundHttpException(\sprintf('No autocompleter found for "%s". Available autocompleters are: (%s)', $alias, implode(', ', $this->autocompleteFieldRegistry->getAutocompleterNames())));
        }

        if ($autocompleter instanceof OptionsAwareAutocompleterInterface || $autocompleter instanceof OptionsAwareEntityAutocompleterInterface) {
            $extraOptions = $this->getExtraOptionsFromRequest($request);
            $autocompleter->setOptions([self::EXTRA_OPTIONS => $extraOptions]);
        }

        $page = $request->query->getInt('page', 1);
        $nextPage = null;

        $data = $this->autocompleteResultsExecutor->fetchResults($autocompleter, $request->query->get('query', ''), $page);

        if ($data->hasNextPage) {
            $parameters = array_merge($request->attributes->all('_route_params'), $request->query->all(), ['page' => $page + 1]);

            $nextPage = $this->urlGenerator->generate($request->attributes->get('_route'), $parameters);
        }

        return new JsonResponse([
            'results' => ($data->optgroups) ? ['options' => $data->results, 'optgroups' => $data->optgroups] : $data->results,
            'next_page' => $nextPage,
        ]);
    }

    /**
     * @return array<string, scalar|array|null>
     */
    private function getExtraOptionsFromRequest(Request $request): array
    {
        if (!$request->query->has(self::EXTRA_OPTIONS)) {
            return [];
        }

        try {
            $extraOptions = $this->decodeExtraOptions($request->query->getString(self::EXTRA_OPTIONS));
        } catch (\JsonException $e) {
            throw new BadRequestHttpException('The extra options cannot be parsed.', $e);
        }

        if (!\array_key_exists(AutocompleteChoiceTypeExtension::CHECKSUM_KEY, $extraOptions)) {
            throw new BadRequestHttpException('The extra options are missing the checksum.');
        }

        $this->validateExtraOptionsChecksum($extraOptions[AutocompleteChoiceTypeExtension::CHECKSUM_KEY], $extraOptions);

        return $extraOptions;
    }

    /**
     * @return array<string, scalar>
     */
    private function decodeExtraOptions(string $extraOptions): array
    {
        return json_decode(base64_decode($extraOptions), true, flags: \JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, scalar> $extraOptions
     */
    private function validateExtraOptionsChecksum(string $checksum, array $extraOptions): void
    {
        $extraOptionsWithoutChecksum = array_filter(
            $extraOptions,
            static fn (string $key) => AutocompleteChoiceTypeExtension::CHECKSUM_KEY !== $key,
            \ARRAY_FILTER_USE_KEY,
        );

        if (!hash_equals($this->checksumCalculator->calculateForArray($extraOptionsWithoutChecksum), $checksum)) {
            throw new BadRequestHttpException('The extra options have been tampered with.');
        }
    }
}
