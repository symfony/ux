import { writeFileSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';
import { loadConfig } from '@pandacss/config';
import { createContext } from '@pandacss/fixture';
import presetBase from '@pandacss/preset-base';
import presetPanda from '@pandacss/preset-panda';
import { test } from 'vitest';
import { toJson } from './config-json';

test('dump resolved configs', async () => {
    const outDir = process.env.GOLDEN_CONFIGS_OUT as string;
    mkdirSync(outDir, { recursive: true });

    writeFileSync(
        join(outDir, 'fixture.json'),
        JSON.stringify(toJson(createContext().config as unknown as Record<string, unknown>), null, 2) + '\n'
    );

    const preset = {
        conditions: presetBase.conditions,
        utilities: presetBase.utilities,
        theme: { breakpoints: presetPanda.theme.breakpoints, containerSizes: presetPanda.theme.containerSizes },
    };
    writeFileSync(process.env.GOLDEN_PRESET_OUT as string, JSON.stringify(toJson(preset), null, 2) + '\n');

    for (const scenario of (process.env.GOLDEN_SCENARIOS ?? '').split(',').filter(Boolean)) {
        const cwd = join(process.cwd(), 'sandbox/codegen');
        const { config } = await loadConfig({ cwd, file: join(cwd, `panda.${scenario}.config.ts`) });
        writeFileSync(
            join(outDir, `codegen-${scenario}.json`),
            JSON.stringify(toJson(config as Record<string, unknown>), null, 2) + '\n'
        );
    }
});
