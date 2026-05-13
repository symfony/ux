/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { describe, expect, it, vi } from 'vitest';
import { createAdapter, detectVersion } from '../../src/leafletAdapter';

/**
 * Returns the synthesized v2 legacy namespace. Wraps the public
 * `createAdapter` rather than the internal `buildLegacyNamespace` so that
 * the implementation module's surface stays minimal.
 */
function legacyFrom(mod: any) {
    return createAdapter(mod).legacyNamespace;
}

/**
 * Minimal fake of the v1 Leaflet module. Exposes lowercase factory
 * functions that return plain spy objects stamped with the ctor name.
 */
function buildV1Fake() {
    const spy = (name: string) =>
        vi.fn((...args: any[]) => ({
            __ctor: name,
            __factory: true,
            args,
            addTo: vi.fn().mockReturnThis(),
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

/**
 * Minimal fake of the v2 Leaflet module. Exposes only the capitalized
 * named class exports. Note: `Map`, `Marker`, etc. must be real ES
 * classes so `detectVersion`'s `Function.prototype.toString` probe
 * sees the `class` keyword.
 */
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

describe('detectVersion', () => {
    it('returns 1 for a v1 module', () => {
        expect(detectVersion(buildV1Fake() as any)).toBe(1);
    });

    it('returns 2 for a v2 module', () => {
        expect(detectVersion(buildV2Fake() as any)).toBe(2);
    });

    it('falls back to 1 when the version field is missing', () => {
        expect(detectVersion({} as any)).toBe(1);
    });

    it('treats 2.0.0-alpha.1 as v2', () => {
        expect(detectVersion({ version: '2.0.0-alpha.1' } as any)).toBe(2);
    });
});

describe('createAdapter (v1)', () => {
    it('calls the lowercase factory for createMap', () => {
        const fake = buildV1Fake();
        const adapter = createAdapter(fake as any);
        const container = document.createElement('div');
        const opts = { zoom: 5 } as any;

        adapter.createMap(container, opts);

        expect(fake.map).toHaveBeenCalledWith(container, opts);
    });

    it('calls the lowercase factory for createMarker', () => {
        const fake = buildV1Fake();
        const adapter = createAdapter(fake as any);

        adapter.createMarker([1, 2], { title: 'x' } as any);

        expect(fake.marker).toHaveBeenCalledWith([1, 2], { title: 'x' });
    });

    it('calls the lowercase factory for createTileLayer', () => {
        const fake = buildV1Fake();
        const adapter = createAdapter(fake as any);

        adapter.createTileLayer('u', { attribution: 'a' } as any);

        expect(fake.tileLayer).toHaveBeenCalledWith('u', { attribution: 'a' });
    });

    it('uses the real module as the legacyNamespace (zero-cost passthrough)', () => {
        const fake = buildV1Fake();
        const adapter = createAdapter(fake as any);

        expect(adapter.legacyNamespace).toBe(fake);
    });

    it('routes createAttributionControl and createZoomControl through control.*', () => {
        const fake = buildV1Fake();
        const adapter = createAdapter(fake as any);

        adapter.createAttributionControl({ position: 'topright' } as any);
        adapter.createZoomControl({ position: 'topleft' } as any);

        expect(fake.control.attribution).toHaveBeenCalledWith({ position: 'topright' });
        expect(fake.control.zoom).toHaveBeenCalledWith({ position: 'topleft' });
    });
});

describe('createAdapter (v2)', () => {
    it('instantiates Map class for createMap', () => {
        const fake = buildV2Fake();
        const adapter = createAdapter(fake as any);
        const container = document.createElement('div');
        const opts = { zoom: 5 } as any;

        const result = adapter.createMap(container, opts);

        expect(result).toBeInstanceOf(fake.Map);
        expect((result as any).container).toBe(container);
        expect((result as any).options).toBe(opts);
    });

    it('instantiates Marker class for createMarker', () => {
        const fake = buildV2Fake();
        const adapter = createAdapter(fake as any);

        const result = adapter.createMarker([1, 2] as any, { title: 'x' } as any);

        expect(result).toBeInstanceOf(fake.Marker);
        expect((result as any).latlng).toEqual([1, 2]);
        expect((result as any).options).toEqual({ title: 'x' });
    });

    it('instantiates TileLayer class for createTileLayer', () => {
        const fake = buildV2Fake();
        const adapter = createAdapter(fake as any);

        const result = adapter.createTileLayer('u', { attribution: 'a' } as any);

        expect(result).toBeInstanceOf(fake.TileLayer);
    });

    it('routes createAttributionControl and createZoomControl through Control.Attribution / Control.Zoom', () => {
        const fake = buildV2Fake();
        const adapter = createAdapter(fake as any);

        const a = adapter.createAttributionControl({ position: 'topright' } as any);
        const z = adapter.createZoomControl({ position: 'topleft' } as any);

        expect(a).toBeInstanceOf(fake.Control.Attribution);
        expect(z).toBeInstanceOf(fake.Control.Zoom);
    });
});

describe('legacyNamespace (v2 shim)', () => {
    it('icon() produces an instance of the v2 Icon class', () => {
        const fake = buildV2Fake();
        const ns = legacyFrom(fake);

        const icon = ns.icon({ iconUrl: 'foo.png' });

        expect(icon).toBeInstanceOf(fake.Icon);
    });

    it('divIcon() produces an instance of the v2 DivIcon class', () => {
        const fake = buildV2Fake();
        const ns = legacyFrom(fake);

        expect(ns.divIcon({ html: '<span/>' })).toBeInstanceOf(fake.DivIcon);
    });

    it('marker() produces an instance of the v2 Marker class', () => {
        const fake = buildV2Fake();
        const ns = legacyFrom(fake);

        expect(ns.marker([1, 2], {})).toBeInstanceOf(fake.Marker);
    });

    it('control.attribution() / control.zoom() / control.layers() / control.scale() produce v2 Control.* instances', () => {
        const fake = buildV2Fake();
        const ns = legacyFrom(fake);

        expect(ns.control.attribution({})).toBeInstanceOf(fake.Control.Attribution);
        expect(ns.control.zoom({})).toBeInstanceOf(fake.Control.Zoom);
        expect(ns.control.layers({}, {}, {})).toBeInstanceOf(fake.Control.Layers);
        expect(ns.control.scale({})).toBeInstanceOf(fake.Control.Scale);
    });

    it('exposes class references for Map/Marker/Icon/etc.', () => {
        const fake = buildV2Fake();
        const ns = legacyFrom(fake);

        expect(ns.Map).toBe(fake.Map);
        expect(ns.Marker).toBe(fake.Marker);
        expect(ns.Icon).toBe(fake.Icon);
        expect(ns.Popup).toBe(fake.Popup);
        expect(ns.TileLayer).toBe(fake.TileLayer);
    });

    it('control itself is callable and returns a Control instance', () => {
        const fake = buildV2Fake();
        const ns = legacyFrom(fake);

        const c = ns.control({ position: 'topright' } as any);

        expect(c).toBeInstanceOf(fake.Control);
    });
});

describe('bridgeOptions forwarding', () => {
    it('v1: forwards bridgeOptions verbatim into the marker factory', () => {
        const fake = buildV1Fake();
        const adapter = createAdapter(fake as any);
        const bridgeOptions = { draggable: true, opacity: 0.5, riseOnHover: true };

        adapter.createMarker([0, 0], bridgeOptions as any);

        expect(fake.marker).toHaveBeenCalledWith([0, 0], bridgeOptions);
    });

    it('v2: forwards bridgeOptions verbatim into the Marker constructor', () => {
        const fake = buildV2Fake();
        const adapter = createAdapter(fake as any);
        const bridgeOptions = { draggable: true, opacity: 0.5, riseOnHover: true };

        const m = adapter.createMarker([0, 0] as any, bridgeOptions as any);

        expect((m as any).options).toBe(bridgeOptions);
    });
});
