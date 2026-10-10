import { describe, expect, it } from 'vitest';
import { createRouter } from '../../src/router_controller';
import { routes } from '../fixtures/routes/index.js';

describe('documentation examples', () => {
    const { path, url } = createRouter({
        routes,
        context: { host: 'example.com', scheme: 'https', pathInfo: '/blog', parameters: { _locale: 'en' } },
    });

    it('generates paths and URLs', () => {
        expect(path('app_blog_show', { slug: 'hello', _query: { page: 2 }, _fragment: 'comments' })).toBe(
            '/blog/hello?page=2#comments'
        );
        expect(path('app_blog_show', { slug: 'hello' }, true)).toBe('blog/hello');
        expect(url('app_blog_show', { slug: 'hello' })).toBe('https://example.com/blog/hello');
        expect(url('app_blog_show', { slug: 'hello' }, true)).toBe('//example.com/blog/hello');
    });

    it('generates localized routes', () => {
        expect(path('app_about')).toBe('/about');
        expect(path('app_about', { _locale: 'fr' })).toBe('/a-propos');
    });

    it('omits optional parameters equal to their default', () => {
        expect(path('app_blog_list')).toBe('/blog');
        expect(path('app_blog_list', { page: 2 })).toBe('/blog/2');
    });
});
