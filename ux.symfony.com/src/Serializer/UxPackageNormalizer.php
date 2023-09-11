<?php

namespace App\Serializer;

use App\Model\UxPackage;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UxPackageNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer,
        private Packages $assets,
        private UrlGeneratorInterface $router,
    ) {
    }

    /**
     * @param UxPackage $object
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        $data['url'] = $this->router->generate($data['route']);
        unset($data['route']);
        $data['imageUrl'] = $this->assets->getUrl('images/ux_packages/'.$data['imageFilename']);

        return $data;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof UxPackage;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [UxPackage::class => true];
    }
}
