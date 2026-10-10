import { expectTypeOf, test } from 'vitest';
import { createRouter, type RouteDefinition } from '../../src/router_controller';
import { routes } from '../fixtures/routes/index.js';

const { path, url } = createRouter({ routes });

test('exposes non-localized names and canonical names only', () => {
    expectTypeOf(path)
        .parameter(0)
        .toEqualTypeOf<
            | 'app_home'
            | 'app_blog_list'
            | 'app_blog_show'
            | 'app_about'
            | 'app_lang'
            | 'app_host'
            | 'app_secure'
            | 'app_controller'
            | 'legacy_search'
        >();

    // @ts-expect-error localized variants are hidden
    path('app_about.en');
    // @ts-expect-error unknown route
    path('app_admin_dashboard');
});

test('requires required variables', () => {
    path('app_blog_show', { slug: 'hello' });
    path('app_blog_show', { slug: 42 });
    // @ts-expect-error missing parameters
    path('app_blog_show');
    // @ts-expect-error missing slug
    path('app_blog_show', {});
});

test('rejects unknown keys', () => {
    // @ts-expect-error typo
    path('app_blog_show', { slug: 'hello', sulg: 'x' });
    // @ts-expect-error extra parameters go through _query
    path('app_home', { page: 2 });
});

test('optional variables and special parameters', () => {
    path('app_blog_list');
    path('app_blog_list', { page: 2 });
    path('app_home', { _query: { page: 2, tags: ['a', 'b'] }, _fragment: 'top' });
    path('app_home', {}, true);
    url('app_home', {}, true);
});

test('literal requirements and locales', () => {
    path('app_lang', { _locale: 'fr' });
    // @ts-expect-error not in the requirement
    path('app_lang', { _locale: 'de' });
    path('app_about');
    path('app_about', { _locale: 'en' });
    // @ts-expect-error no variant for this locale
    path('app_about', { _locale: 'de' });
    path('app_host', { subdomain: 'm' });
    // @ts-expect-error not in the requirement
    path('app_host', { subdomain: 'api' });
});

test('untyped routes accept anything', () => {
    const router = createRouter({ routes: {} as Record<string, RouteDefinition> });

    router.path('anything', { any: 1 });
    expectTypeOf(router.url('anything')).toEqualTypeOf<string>();
});
