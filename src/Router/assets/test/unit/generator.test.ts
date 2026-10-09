import { describe, expect, it } from 'vitest';
import { getRelativePath } from '../../src/generator';
import { createRouter, RouteNotFoundError, type Routes } from '../../src/router_controller';

const context = { host: 'localhost', scheme: 'http', pathInfo: '/', parameters: { _locale: 'en' } };

describe('getRelativePath', () => {
    it.each([
        ['/a/b/c/d', '/a/b/c/d', ''],
        ['/a/b/c/d', '/a/b/c/', './'],
        ['/a/b/c/d', '/a/b/', '../'],
        ['/a/b/c/d', '/a/b/c/other', 'other'],
        ['/a/b/c/d', '/a/x/y', '../../x/y'],
        ['/', '/x:y', './x:y'],
    ])('from %s to %s', (base, target, expected) => {
        expect(getRelativePath(base, target)).toBe(expected);
    });
});

describe('route lookup', () => {
    it.each(['constructor', 'toString', '__proto__', 'hasOwnProperty'])(
        'does not resolve "%s" from the prototype',
        (name) => {
            const { path } = createRouter({ routes: {} as Routes, context });

            expect(() => path(name as never)).toThrowError(RouteNotFoundError);
        }
    );
});

describe('requirements', () => {
    it('skips the check of a requirement converted to null', () => {
        const routes: Routes = {
            item: {
                tokens: [
                    ['variable', '/', null, 'id'],
                    ['text', '/item'],
                ],
                defaults: {},
                hostTokens: [],
                schemes: [],
            },
        };

        expect(createRouter({ routes, context }).path('item', { id: 'anything at all' })).toBe(
            '/item/anything%20at%20all'
        );
    });

    it('skips the check of a requirement JavaScript cannot compile', () => {
        const routes: Routes = {
            item: {
                tokens: [
                    ['variable', '/', '\\-', 'id', true],
                    ['text', '/item'],
                ],
                defaults: {},
                hostTokens: [],
                schemes: [],
            },
        };

        expect(createRouter({ routes, context }).path('item', { id: 'x' })).toBe('/item/x');
    });
});

describe('query defaults', () => {
    const routes: Routes = {
        query: { tokens: [['text', '/query']], defaults: { _query: { sort: 'asc' } }, hostTokens: [], schemes: [] },
    };

    it('applies a "_query" default', () => {
        expect(createRouter({ routes, context }).path('query')).toBe('/query?sort=asc');
    });

    it('merges the "_query" parameter over the "_query" default', () => {
        expect(createRouter({ routes, context }).path('query', { _query: { sort: 'desc', page: 2 } })).toBe(
            '/query?sort=desc&page=2'
        );
    });
});
