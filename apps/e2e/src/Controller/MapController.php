<?php

namespace App\Controller;

use App\MapRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Map\Circle;
use Symfony\UX\Map\Icon\Icon;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Polygon;
use Symfony\UX\Map\Polyline;
use Symfony\UX\Map\Rectangle;

#[Route('/ux-map')]
final class MapController extends AbstractController
{
    #[Route('/basic')]
    public function basic(
        #[MapQueryParameter] MapRenderer $renderer
    ): Response {
        $map = (new Map(rendererName: $renderer->value))
            ->center(new Point(48.8566, 2.3522))
            ->zoom(12)
        ;

        return $this->render('ux_map/render_map.html.twig', ['map' => $map]);
    }

    #[Route('/with-markers-and-fit-bounds-to-markers')]
    public function withMarkersAndFitBoundsToMarkers(#[MapQueryParameter] MapRenderer $renderer): Response
    {
        $map = (new Map(rendererName: $renderer->value))
            ->fitBoundsToMarkers()
            ->addMarker(new Marker(
                position: new Point(48.8566, 2.3522),
                title: 'Paris',
            ))
            ->addMarker(new Marker(
                position: new Point(45.7640, 4.8357),
                title: 'Lyon',
            ))
        ;

        return $this->render('ux_map/render_map.html.twig', ['map' => $map]);
    }

    #[Route('/with-markers-and-zoomed-on-paris')]
    public function withMarkersZoomedOnParis(#[MapQueryParameter] MapRenderer $renderer): Response
    {
        $map = (new Map(rendererName: $renderer->value))
            ->center(new Point(48.8566, 2.3522))
            ->zoom(10)
            ->addMarker(new Marker(
                position: new Point(48.8566, 2.3522),
                title: 'Paris',
            ))
            ->addMarker(new Marker(
                position: new Point(45.7640, 4.8357),
                title: 'Lyon',
            ))
        ;

        return $this->render('ux_map/render_map.html.twig', ['map' => $map]);
    }

    #[Route('/with-markers-and-info-windows')]
    public function withMarkersAndInfoWindows(#[MapQueryParameter] MapRenderer $renderer): Response
    {
        $map = (new Map(rendererName: $renderer->value))
            ->fitBoundsToMarkers()
            ->addMarker(new Marker(
                position: new Point(48.8566, 2.3522),
                title: 'Paris',
                infoWindow: new InfoWindow(content: 'Capital of France', opened: true),
            ))
            ->addMarker(new Marker(
                position: new Point(45.7640, 4.8357),
                title: 'Lyon',
                infoWindow: new InfoWindow(content: 'Famous for its gastronomy'),
            ))
        ;

        return $this->render('ux_map/render_map.html.twig', ['map' => $map]);
    }

    #[Route('/with-markers-and-custom-icons')]
    public function withMarkersAndCustomIcons(
        #[MapQueryParameter] MapRenderer $renderer,
        #[Autowire(service: 'asset_mapper.asset_package')] PackageInterface $package,
    ): Response
    {
        $map = (new Map(rendererName: $renderer->value))
            ->fitBoundsToMarkers()
            ->addMarker(new Marker(
                position: new Point(48.8566, 2.3522),
                title: 'Paris',
                icon: Icon::ux('mdi:eiffel-tower'),
            ))
            ->addMarker(new Marker(
                position: new Point(45.7640, 4.8357),
                title: 'Lyon',
                icon: Icon::svg('<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4"/></svg>')
            ))
            ->addMarker(new Marker(
                position: new Point(44.8378, -0.5792),
                title: 'Bordeaux',
                icon: Icon::url($package->getUrl('icons/mdi/glass-wine.svg'))
            ))
        ;

        return $this->render('ux_map/render_map.html.twig', ['map' => $map]);
    }

    #[Route('/with-polygons')]
    public function withPolygons(#[MapQueryParameter] MapRenderer $renderer): Response
    {
        $map = (new Map(rendererName: $renderer->value))
            ->center(new Point(48.8566, 2.3522))
            ->zoom(5)
            ->addPolygon(new Polygon(
                points: [
                    // First path, the outer boundary of the polygon
                    [
                        new Point(48.117266, -1.677792), // Rennes
                        new Point(50.629250, 3.057256), // Lille
                        new Point(48.573405, 7.752111), // Strasbourg
                        new Point(43.296482, 5.369780), // Marseille
                        new Point(44.837789, -0.579180), // Bordeaux
                    ],
                    // Second path, making a hole in the first path
                    [
                        new Point(45.833619, 1.261105), // Limoges
                        new Point(45.764043, 4.835659), // Lyon
                        new Point(49.258329, 4.031696), // Reims
                        new Point(48.856613, 2.352222), // Paris
                    ],
                ],
                infoWindow: new InfoWindow(content: 'A weird shape on France'),
            ))

            ->addPolygon(new Polygon(
                points: [
                    new Point(41.902782, 12.496366), // Rome
                    new Point(45.464211, 9.191383), // Milan
                    new Point(44.494887, 11.342616), // Bologna
                    new Point(40.851798, 14.268124), // Naples
                ],
                infoWindow: new InfoWindow(content: 'A polygon covering some of the main cities in Italy'),
            ))
        ;

        return $this->render('ux_map/render_map.html.twig', ['map' => $map]);
    }

    #[Route('/with-polylines')]
    public function withPolylines(#[MapQueryParameter] MapRenderer $renderer): Response
    {
        $map = (new Map(rendererName: $renderer->value))
            ->center(new Point(48.8566, 2.3522))
            ->zoom(5)
            ->addPolyline(new Polyline(
                points: [
                    new Point(48.8566, 2.3522), // Paris
                    new Point(45.7640, 4.8357), // Lyon
                    new Point(43.2965, 5.3698), // Marseille
                    new Point(44.8378, -0.5792), // Bordeaux
                ],
                infoWindow: new InfoWindow(
                    content: 'A line passing through Paris, Lyon, Marseille, Bordeaux',
                ),
            ))
            ->addPolyline(new Polyline(
                points: [
                    new Point(48.1173, -1.6778), // Rennes
                    new Point(47.2184, -1.5536), // Nantes
                    new Point(47.3493, 0.7484), // Tours
                ],
                infoWindow: new InfoWindow(
                    content: 'A line passing through Rennes, Nantes and Tours'
                )
            ))
        ;

        return $this->render('ux_map/render_map.html.twig', ['map' => $map]);
    }

    #[Route('/with-circles')]
    public function withCircles(#[MapQueryParameter] MapRenderer $renderer): Response
    {
        $map = (new Map(rendererName: $renderer->value))
            ->center(new Point(48.8566, 2.3522))
            ->zoom(5)
            ->addCircle(new Circle(
                center: new Point(48.8566, 2.3522),
                radius: 50_000,
                infoWindow: new InfoWindow(
                    content: 'A 50km radius circle centered on Paris',
                ),
            ))
            ->addCircle(new Circle(
                center: new Point(45.7640, 4.8357),
                radius: 30_000,
                infoWindow: new InfoWindow(
                    content: 'A 30km radius circle centered on Lyon',
                ),
            ))
        ;

        return $this->render('ux_map/render_map.html.twig', ['map' => $map]);
    }

    #[Route('/with-rectangles')]
    public function withRectangles(#[MapQueryParameter] MapRenderer $renderer): Response
    {
        $map = (new Map(rendererName: $renderer->value))
            ->center(new Point(48.8566, 2.3522))
            ->zoom(5)
            ->addRectangle(new Rectangle(
                southWest: new Point(48.8566, 2.3522), // Paris
                northEast: new Point(50.6292, 3.0573), // Lille
                infoWindow: new InfoWindow(
                    content: 'A rectangle from Paris to Lille',
                ),
            ))
            ->addRectangle(new Rectangle(
                southWest: new Point(44.8378, -0.5792), // Bordeaux
                northEast: new Point(45.7640, 4.8357), // Lyon
                infoWindow: new InfoWindow(
                    content: 'A rectangle from Bordeaux to Lyon',
                ),
            ))
        ;

        return $this->render('ux_map/render_map.html.twig', ['map' => $map]);
    }
}
