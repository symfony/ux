/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { describe, expect, it, vi } from 'vitest';
import { createAdapter } from '../../src/leafletAdapter';

/**
 * Mirrors the v1/v2 fakes used by the adapter tests. Kept in-file to keep
 * the test self-contained and avoid a shared fixture module for a small
 * test surface.
 */
function buildV1Fake() {
    const spy = (name: string) =>
        vi.fn((...args: any[]) => ({
            __ctor: name,
            args,
            addTo: vi.fn().mockReturnThis(),
            setIcon: vi.fn(),
        }));
    return {
        version: '1.9.4',
        map: spy('Map'),
        tileLayer: spy('TileLayer'),
        marker: spy('Marker'),
        icon: spy('Icon'),
        divIcon: spy('DivIcon'),
        popup: spy('Popup'),
        polygon: spy('Polygon'),
        polyline: spy('Polyline'),
        circle: spy('Circle'),
        rectangle: spy('Rectangle'),
        latLngBounds: spy('LatLngBounds'),
        control: Object.assign(spy('Control'), {
            attribution: spy('Control.Attribution'),
            zoom: spy('Control.Zoom'),
            layers: spy('Control.Layers'),
            scale: spy('Control.Scale'),
        }),
    };
}

function buildV2Fake() {
    class Map {
        constructor(
            public container: unknown,
            public options: unknown
        ) {}
        addTo = vi.fn().mockReturnThis();
    }
    class TileLayer {
        constructor(
            public url: unknown,
            public options: unknown
        ) {}
        addTo = vi.fn().mockReturnThis();
    }
    class Marker {
        constructor(
            public latlng: unknown,
            public options: unknown
        ) {}
        addTo = vi.fn().mockReturnThis();
        setIcon = vi.fn();
    }
    class Icon {
        constructor(public options: unknown) {}
    }
    class DivIcon {
        constructor(public options: unknown) {}
    }
    class Popup {
        constructor(public options: unknown) {}
    }
    class Polygon {
        constructor(
            public latlngs: unknown,
            public options: unknown
        ) {}
        addTo = vi.fn().mockReturnThis();
    }
    class Polyline {
        constructor(
            public latlngs: unknown,
            public options: unknown
        ) {}
        addTo = vi.fn().mockReturnThis();
    }
    class Circle {
        constructor(
            public latlng: unknown,
            public options: unknown
        ) {}
        addTo = vi.fn().mockReturnThis();
    }
    class Rectangle {
        constructor(
            public bounds: unknown,
            public options: unknown
        ) {}
        addTo = vi.fn().mockReturnThis();
    }
    class LatLngBounds {
        constructor(
            public c1: unknown,
            public c2: unknown
        ) {}
    }
    class ControlAttribution {
        constructor(public options: unknown) {}
        addTo = vi.fn().mockReturnThis();
    }
    class ControlZoom {
        constructor(public options: unknown) {}
        addTo = vi.fn().mockReturnThis();
    }
    class ControlLayers {
        constructor(
            public baseLayers: unknown,
            public overlays: unknown,
            public options: unknown
        ) {}
    }
    class ControlScale {
        constructor(public options: unknown) {}
    }
    class Control {
        constructor(public options: unknown) {}
    }

    return {
        version: '2.0.0',
        Map,
        TileLayer,
        Marker,
        Icon,
        DivIcon,
        Popup,
        Polygon,
        Polyline,
        Circle,
        Rectangle,
        LatLngBounds,
        Control: Object.assign(Control, {
            Attribution: ControlAttribution,
            Zoom: ControlZoom,
            Layers: ControlLayers,
            Scale: ControlScale,
        }),
    };
}

describe('event.detail.L contract via adapter.legacyNamespace', () => {
    it('v1: event.detail.L is the real module, and L.icon() calls the v1 factory', () => {
        const fake = buildV1Fake();
        const adapter = createAdapter(fake as any);

        const L = adapter.legacyNamespace;
        L.icon({ iconUrl: 'foo.png' });

        expect(fake.icon).toHaveBeenCalledWith({ iconUrl: 'foo.png' });
    });

    it('v2: event.detail.L.icon() produces an instance of the v2 Icon class', () => {
        const fake = buildV2Fake();
        const adapter = createAdapter(fake as any);

        const L = adapter.legacyNamespace;
        const icon = L.icon({ iconUrl: 'foo.png' });

        expect(icon).toBeInstanceOf(fake.Icon);
    });

    it('v2: existing consumer snippet from the README runs unchanged', () => {
        // Reproduces the documented `_onMarkerBeforeCreate` handler body
        // from README.md against the v2 legacy namespace.
        const fake = buildV2Fake();
        const adapter = createAdapter(fake as any);
        const L = adapter.legacyNamespace;

        const redIcon = L.icon({
            iconUrl: 'https://leafletjs.com/examples/custom-icons/leaf-red.png',
            shadowUrl: 'https://leafletjs.com/examples/custom-icons/leaf-shadow.png',
            iconSize: [38, 95],
            shadowSize: [50, 64],
            iconAnchor: [22, 94],
            shadowAnchor: [4, 62],
            popupAnchor: [-3, -76],
        } as any);

        expect(redIcon).toBeInstanceOf(fake.Icon);
    });

    it('v1 and v2: divIcon and marker both yield usable instances', () => {
        const v1 = buildV1Fake();
        const v2 = buildV2Fake();
        const a1 = createAdapter(v1 as any);
        const a2 = createAdapter(v2 as any);

        expect(typeof a1.legacyNamespace.divIcon).toBe('function');
        expect(typeof a1.legacyNamespace.marker).toBe('function');
        expect(a2.legacyNamespace.divIcon({ html: '<span/>' })).toBeInstanceOf(v2.DivIcon);
        expect(a2.legacyNamespace.marker([0, 0], {})).toBeInstanceOf(v2.Marker);
    });
});

describe('adapter routing: v1 factory vs v2 constructor', () => {
    it('v1 createPolygon/Polyline/Circle/Rectangle all call the lowercase factory with identical args', () => {
        const fake = buildV1Fake();
        const adapter = createAdapter(fake as any);

        adapter.createPolygon([[1, 2]], { color: 'red' } as any);
        adapter.createPolyline([[3, 4]], { color: 'green' } as any);
        adapter.createCircle([5, 6] as any, { radius: 100 } as any);
        adapter.createRectangle(
            [
                [7, 8],
                [9, 10],
            ] as any,
            { color: 'blue' } as any
        );

        expect(fake.polygon).toHaveBeenCalledWith([[1, 2]], { color: 'red' });
        expect(fake.polyline).toHaveBeenCalledWith([[3, 4]], { color: 'green' });
        expect(fake.circle).toHaveBeenCalledWith([5, 6], { radius: 100 });
        expect(fake.rectangle).toHaveBeenCalledWith(
            [
                [7, 8],
                [9, 10],
            ],
            { color: 'blue' }
        );
    });

    it('v2 createPolygon/Polyline/Circle/Rectangle all instantiate the class export', () => {
        const fake = buildV2Fake();
        const adapter = createAdapter(fake as any);

        expect(adapter.createPolygon([[1, 2]] as any, { color: 'red' } as any)).toBeInstanceOf(fake.Polygon);
        expect(adapter.createPolyline([[3, 4]] as any, { color: 'green' } as any)).toBeInstanceOf(fake.Polyline);
        expect(adapter.createCircle([5, 6] as any, { radius: 100 } as any)).toBeInstanceOf(fake.Circle);
        expect(
            adapter.createRectangle(
                [
                    [7, 8],
                    [9, 10],
                ] as any,
                { color: 'blue' } as any
            )
        ).toBeInstanceOf(fake.Rectangle);
    });
});
