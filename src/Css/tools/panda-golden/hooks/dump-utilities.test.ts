import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname } from 'node:path';
import { createContext } from '@pandacss/fixture';
import { test } from 'vitest';

const SAMPLES: Array<string | number | boolean> = [
    'red',
    'red.300/40',
    '10px',
    12,
    0,
    'auto',
    '{spacing.4}',
    'token(colors.red.300, blue)',
    'a  b',
    true,
];

const record = (
    utility: ReturnType<typeof createContext>['utility'],
    prop: string,
    value: string | number | boolean
) => {
    try {
        const { className, styles, layer } = utility.transform(prop, value as string);
        return layer ? [value, className, styles, layer] : [value, className, styles];
    } catch (error) {
        return [value, { error: (error as Error).message }];
    }
};

test('dump utility transforms', () => {
    const { utility } = createContext();
    const cases: Record<string, unknown[]> = {};

    for (const prop of Object.keys(utility.config)) {
        const keys = utility.getPropertyKeys(prop);
        const values = [...new Set([...keys.slice(0, 3), ...keys.slice(-1), ...SAMPLES])];
        cases[prop] = values.map((value) => record(utility, prop, value));
    }

    for (const [shorthand, prop] of utility.shorthands) {
        const [value] = utility.getPropertyKeys(prop);
        cases[shorthand] ??= [record(utility, shorthand, value ?? 'red')];
    }

    const lines = Object.entries(cases).map(
        ([prop, entries]) =>
            `  ${JSON.stringify(prop)}: [\n${entries.map((entry) => `    ${JSON.stringify(entry)}`).join(',\n')}\n  ]`
    );
    mkdirSync(dirname(process.env.GOLDEN_UTILITIES_OUT as string), { recursive: true });
    writeFileSync(process.env.GOLDEN_UTILITIES_OUT as string, `{\n${lines.join(',\n')}\n}\n`);
});
