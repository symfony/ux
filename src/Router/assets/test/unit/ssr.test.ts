// @vitest-environment node
import { expect, it } from 'vitest';
import { createRouter, type Routes } from '../../src/router_controller';

const routes: Routes = {
    'about.en': {
        tokens: [['text', '/about']],
        defaults: { _locale: 'en', _canonical_route: 'about' },
        hostTokens: [],
        schemes: [],
    },
};

it('generates without window nor document', () => {
    const { path, url } = createRouter({ routes });

    expect(path('about')).toBe('/about');
    expect(url('about')).toBe('/about');
});
