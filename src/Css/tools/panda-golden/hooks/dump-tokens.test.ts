import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname } from 'node:path';
import { createContext } from '@pandacss/fixture';
import { test } from 'vitest';

const toObject = (map: Map<string, unknown>) => Object.fromEntries(map);

test('dump token views', () => {
    const { tokens } = createContext();
    const view = tokens.view;
    const views = {
        vars: Object.fromEntries([...view.values].map(([name, value]) => [name, value])),
        values: Object.fromEntries([...tokens.byName].map(([name, token]) => [name, token.value])),
        categories: Object.fromEntries(
            [...view.valuesByCategory].map(([category, values]) => [category, toObject(values)])
        ),
        colorPalettes: Object.fromEntries(
            [...view.colorPalettes].map(([palette, values]) => [palette, toObject(values)])
        ),
        cssVars: Object.fromEntries([...view.vars].map(([condition, values]) => [condition, toObject(values)])),
    };
    mkdirSync(dirname(process.env.GOLDEN_TOKENS_OUT as string), { recursive: true });
    writeFileSync(process.env.GOLDEN_TOKENS_OUT as string, JSON.stringify(views, null, 1) + '\n');
});
