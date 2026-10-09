import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { createRouter, type Routes } from '../../src/router_controller';

declare const jsdom: { reconfigure(options: { url: string }): void };

const routes: Routes = {
    static: { tokens: [['text', '/static/path']], defaults: {}, hostTokens: [], schemes: [] },
    'about.en': {
        tokens: [['text', '/about']],
        defaults: { _locale: 'en', _canonical_route: 'about' },
        hostTokens: [],
        schemes: [],
    },
    'about.fr': {
        tokens: [['text', '/a-propos']],
        defaults: { _locale: 'fr', _canonical_route: 'about' },
        hostTokens: [],
        schemes: [],
    },
};

describe('default context', () => {
    beforeEach(() => {
        jsdom.reconfigure({ url: 'https://example.com:8443/sub/blog/page' });
    });

    afterEach(() => {
        document.documentElement.removeAttribute('lang');
    });

    it('reads host, scheme and port from the location', () => {
        expect(createRouter({ routes }).url('static')).toBe('https://example.com:8443/static/path');
    });

    it('reads the locale from <html lang>', () => {
        document.documentElement.lang = 'fr-FR';

        expect(createRouter({ routes }).path('about')).toBe('/a-propos');
    });

    it('falls back to "en" without <html lang>', () => {
        expect(createRouter({ routes }).path('about')).toBe('/about');
    });

    it('picks up changes between two calls', () => {
        const { path } = createRouter({ routes });
        expect(path('about')).toBe('/about');

        document.documentElement.lang = 'fr';
        window.history.pushState({}, '', '/static/other');

        expect(path('about')).toBe('/a-propos');
        expect(path('static', {}, true)).toBe('path');
    });

    it('strips the base url from the current path', () => {
        expect(createRouter({ routes, context: { baseUrl: '/sub' } }).path('static', {}, true)).toBe('../static/path');
    });

    it('lets the given context win', () => {
        const { url } = createRouter({ routes, context: { host: 'Other.TEST', scheme: 'http', httpPort: 8080 } });

        expect(url('static')).toBe('http://other.test:8080/static/path');
    });
});
