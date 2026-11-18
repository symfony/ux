<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Data\MapBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Map\Bridge\Google\GoogleOptions;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;

#[Route('/map')]
final class MapController extends AbstractController
{
    #[Route('/leaflet', name: 'map_leaflet', methods: ['GET'])]
    public function leaflet(MapBuilder $builder): Response
    {
        $map = $builder->build('default', new LeafletOptions(zoomControl: false));

        return $this->render('map/leaflet.html.twig', ['map' => $map]);
    }

    #[Route('/google', name: 'map_google', methods: ['GET'])]
    public function google(MapBuilder $builder): Response
    {
        $options = new GoogleOptions(mapId: 'benchmarks', streetViewControl: false, fullscreenControl: false);
        $map = $builder->build('google', $options);

        return $this->render('map/google.html.twig', ['map' => $map]);
    }
}
