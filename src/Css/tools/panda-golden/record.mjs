#!/usr/bin/env node

import { execFileSync } from 'node:child_process';
import { cpSync, existsSync, mkdirSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { homedir } from 'node:os';
import { dirname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const PANDA_REPOSITORY = 'https://github.com/chakra-ui/panda.git';
const PANDA_COMMIT = 'e917ab86b1b2285640ccd73550f8857973737c89';
const CODEGEN_SCENARIOS = ['react', 'strict-tokens', 'strict-property-values', 'strict', 'format-names'];

const toolDir = dirname(fileURLToPath(import.meta.url));
const pandaDir =
    process.env.PANDA_DIR ?? join(process.env.XDG_CACHE_HOME ?? join(homedir(), '.cache'), 'symfony-ux-css', 'panda');
const fixturesDir = process.env.GOLDEN_FIXTURES_DIR ?? join(toolDir, '../../tests/Fixtures/Panda');
const outDir = join(pandaDir, 'golden-out', String(Date.now()));
const vitest = join(pandaDir, 'node_modules/.bin/vitest');

const run = (command, args, options = {}) => {
    console.log(`\n$ ${[command, ...args].join(' ')}`);
    execFileSync(command, args, { stdio: 'inherit', ...options, env: { ...process.env, ...options.env } });
};

const replaceOnce = (file, search, replacement) => {
    const content = readFileSync(file, 'utf8');
    const first = content.indexOf(search);
    if (first === -1 || content.indexOf(search, first + 1) !== -1) {
        throw new Error(`Expected exactly one occurrence of ${JSON.stringify(search)} in ${file}.`);
    }
    writeFileSync(file, content.slice(0, first) + replacement + content.slice(first + search.length));
};

const checkoutPanda = () => {
    if (!existsSync(pandaDir)) {
        run('git', ['clone', '--filter=blob:none', '--no-checkout', PANDA_REPOSITORY, pandaDir]);
    }
    run('git', ['-C', pandaDir, 'checkout', '--force', PANDA_COMMIT]);
    run('pnpm', ['install', '--frozen-lockfile'], { cwd: pandaDir });
};

const instrumentPanda = () => {
    cpSync(join(toolDir, 'hooks'), join(pandaDir, 'golden'), { recursive: true });
    cpSync(join(toolDir, 'hooks'), join(pandaDir, 'sandbox/codegen/golden'), { recursive: true });

    const createContext = join(pandaDir, 'packages/fixture/src/create-context.ts');
    replaceOnce(createContext, '  return new PandaContext({', '  const goldenContext = new PandaContext({');
    replaceOnce(
        createContext,
        '      useInMemoryFileSystem: true,\n    },\n  })\n}',
        '      useInMemoryFileSystem: true,\n    },\n  })\n  ;(goldenContext as any).__goldenUserConfig = userConfig ?? null\n  return goldenContext\n}'
    );

    const ruleProcessor = join(pandaDir, 'packages/core/src/rule-processor.ts');
    replaceOnce(
        ruleProcessor,
        "import type { CssOptions, Stylesheet } from '@pandacss/core'\n",
        "import type { CssOptions, Stylesheet } from '@pandacss/core'\nimport { goldenClone, goldenCssCall, goldenOtherCall, goldenToCss } from '../../../golden/hooks'\n"
    );
    replaceOnce(ruleProcessor, '  clone() {\n', '  clone() {\n    goldenClone(this)\n');
    for (const signature of [
        'cva(recipeConfig: RecipeDefinition<any>): AtomicRecipeRule {\n',
        'sva(recipeConfig: SlotRecipeDefinition<string, any>): AtomicRecipeRule {\n',
        'recipe(name: string, variants: Record<string, any> = {}): RecipeVariantsRule | undefined {\n',
    ]) {
        const method = signature.slice(0, signature.indexOf('('));
        replaceOnce(ruleProcessor, `  ${signature}`, `  ${signature}    goldenOtherCall(this, '${method}')\n`);
    }
    replaceOnce(
        ruleProcessor,
        '    const { encoder, decoder } = this.getParamsOrThrow()\n\n    encoder.processAtomic(styles)\n',
        '    const { encoder, decoder } = this.getParamsOrThrow()\n\n    goldenCssCall(this, styles)\n    encoder.processAtomic(styles)\n'
    );
    replaceOnce(
        ruleProcessor,
        '    sheet.processDecoder(decoder)\n    return sheet.toCss(options)\n',
        '    sheet.processDecoder(decoder)\n    return goldenToCss(this, options, sheet.toCss(options))\n'
    );

    const parserFixture = join(pandaDir, 'packages/parser/__tests__/fixture.ts');
    replaceOnce(
        parserFixture,
        "import { createContext } from '@pandacss/fixture'\n",
        "import { createContext } from '@pandacss/fixture'\nimport { goldenParseAndExtract } from '../../../golden/hooks'\n"
    );
    replaceOnce(
        parserFixture,
        '  return {\n    ctx,\n    encoder,\n    styles,\n    json: result?.toArray().flatMap(({ box, ...item }) => item),\n    css: ctx.getParserCss(styles),\n  }\n',
        '  const goldenReturn = {\n    ctx,\n    encoder,\n    styles,\n    json: result?.toArray().flatMap(({ box, ...item }) => item),\n    css: ctx.getParserCss(styles),\n  }\n  goldenParseAndExtract(userConfig ?? null, goldenReturn.json, goldenReturn.css, ctx.config)\n  return goldenReturn\n'
    );

    const tokenTest = join(pandaDir, 'packages/generator/__tests__/generate-token.test.ts');
    replaceOnce(
        tokenTest,
        "import { createContext } from '@pandacss/fixture'\n",
        "import { createContext } from '@pandacss/fixture'\nimport { goldenTokenCss } from '../../../golden/hooks'\n"
    );
    replaceOnce(
        tokenTest,
        "  ctx.appendCssOfType('tokens', sheet)\n  return sheet.toCss()\n}",
        "  ctx.appendCssOfType('tokens', sheet)\n  return goldenTokenCss(config, ctx.config, sheet.toCss())\n}"
    );

    const staticCssTest = join(pandaDir, 'packages/core/__tests__/static-css.test.ts');
    replaceOnce(
        staticCssTest,
        "import { Context } from '../src/context'\n",
        "import { Context } from '../src/context'\nimport { goldenStaticCss } from '../../../golden/hooks'\n"
    );
    const helper =
        /(\n\s+)const engine = (\w+)\.staticCss\.clone\(\)\.process\(options\)\n\s+return \{ results: engine\.results, css: engine\.sheet\.toCss\(\) \}/g;
    const staticCssSource = readFileSync(staticCssTest, 'utf8');
    if (!helper.test(staticCssSource)) throw new Error(`Expected the staticCss helpers in ${staticCssTest}.`);
    writeFileSync(
        staticCssTest,
        staticCssSource.replace(
            helper,
            '$1const engine = $2.staticCss.clone().process(options)$1return goldenStaticCss(options, $2.config, { results: engine.results, css: engine.sheet.toCss() })'
        )
    );

    writeFileSync(
        join(pandaDir, 'sandbox/codegen/vitest.golden.config.ts'),
        "import { defineConfig, mergeConfig } from 'vitest/config'\nimport base from './vitest.config'\n\nexport default mergeConfig(base, defineConfig({ test: { setupFiles: ['./golden/codegen-setup.ts'] } }))\n"
    );
};

const recordFixtureSuite = () => {
    run(
        vitest,
        [
            'run',
            'packages',
            'golden/dump-configs',
            'golden/dump-utilities',
            'golden/dump-tokens',
            'golden/dump-css-types',
        ],
        {
            cwd: pandaDir,
            env: {
                GOLDEN_OUT: join(outDir, 'records'),
                GOLDEN_CONFIGS_OUT: join(outDir, 'configs'),
                GOLDEN_UTILITIES_OUT: join(outDir, 'utilities.json'),
                GOLDEN_TOKENS_OUT: join(outDir, 'tokens.json'),
                GOLDEN_CSS_TYPES_OUT: join(outDir, 'css-types.json'),
                GOLDEN_PRESET_OUT: join(outDir, 'panda-preset.json'),
                GOLDEN_DEFAULT_TOKENS_OUT: join(outDir, 'panda-tokens.json'),
                GOLDEN_SCENARIOS: CODEGEN_SCENARIOS.join(','),
            },
        }
    );
};

const recordCodegenScenarios = () => {
    const sandboxDir = join(pandaDir, 'sandbox/codegen');
    for (const scenario of CODEGEN_SCENARIOS) {
        run(
            join(sandboxDir, 'node_modules/.bin/panda'),
            ['codegen', '--clean', '--config', `panda.${scenario}.config.ts`],
            {
                cwd: sandboxDir,
            }
        );
        const { outdir } = JSON.parse(readFileSync(join(outDir, 'configs', `codegen-${scenario}.json`), 'utf8'));
        replaceOnce(
            join(sandboxDir, outdir, 'css/css.mjs'),
            'export const css = (...styles) => cssFn(mergeCss(...styles))',
            'export const css = (...styles) => {\n  const className = cssFn(mergeCss(...styles))\n  globalThis.__goldenCssHook?.(styles, className)\n  return className\n}'
        );
        run(join(sandboxDir, 'node_modules/.bin/vitest'), ['run', '--config', 'vitest.golden.config.ts'], {
            cwd: sandboxDir,
            env: { MODE: scenario, GOLDEN_SCENARIO: scenario, GOLDEN_OUT: join(outDir, 'records') },
        });
    }
};

const callLines = (lines, start) => {
    const opening = lines[start].search(/\bcss\(/);
    let depth = 0;
    let quote = null;
    for (let index = start; index < lines.length; index++) {
        const line = lines[index];
        if (quote !== '`') quote = null;
        for (let column = index === start ? Math.max(opening, 0) : 0; column < line.length; column++) {
            const char = line[column];
            if (quote) {
                if (char === '\\') column++;
                else if (char === quote) quote = null;
            } else if (char === '/' && line[column + 1] === '/') {
                break;
            } else if (char === "'" || char === '"' || char === '`') {
                quote = char;
            } else if (char === '(') {
                depth++;
            } else if (char === ')' && --depth === 0) {
                return lines.slice(start, index + 1);
            }
        }
    }
    return [lines[start]];
};

const expectsTypeError = (callSite) => {
    if (!callSite) return false;
    const separator = callSite.lastIndexOf(':');
    const lines = readFileSync(callSite.slice(0, separator), 'utf8').split('\n');
    const start = Number(callSite.slice(separator + 1)) - 1;
    if (callLines(lines, start).some((line) => line.includes('@ts-expect-error'))) return true;
    for (let index = start - 1; index >= 0; index--) {
        if (lines[index].trim() !== '') return lines[index].includes('@ts-expect-error');
    }
    return false;
};

let fixtureConfig;

// {$value} wraps set values: PHP decodes JSON objects with integer keys as lists, so a bare value would be ambiguous.
const createMergePatch = (source, target, path = []) => {
    const isObject = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);
    if (!isObject(source) || !isObject(target)) return target === null ? null : { $value: target };

    const patch = {};
    for (const key of Object.keys(source)) {
        if (!(key in target)) patch[key] = null;
    }
    for (const key of Object.keys(target)) {
        if (!(key in source)) patch[key] = target[key] === null ? null : { $value: target[key] };
        else if (JSON.stringify(source[key]) !== JSON.stringify(target[key]))
            patch[key] = createMergePatch(source[key], target[key], [...path, key]);
    }
    const kept = Object.keys(source).filter((key) => key in target);
    const order = [...kept, ...Object.keys(target).filter((key) => !(key in source))];
    // Only the theme's key order reaches the output: it is the order of the token variables.
    if (path[0] === 'theme' && order.join('\0') !== Object.keys(target).join('\0')) patch.$order = Object.keys(target);
    return patch;
};

const configPatch = (json) => {
    if (!json.resolvedConfig) return {};
    fixtureConfig ??= JSON.parse(readFileSync(join(outDir, 'configs', 'fixture.json'), 'utf8'));
    return { configPatch: createMergePatch(fixtureConfig, json.resolvedConfig) };
};

const toCase = (record, id) => {
    const base = {
        id,
        kind: record.kind,
        config: record.scenario === 'fixture' ? 'fixture' : `codegen-${record.scenario}`,
        source: {
            file: relative(pandaDir, record.testPath),
            test: record.testName,
            line: record.callSite ? Number(record.callSite.slice(record.callSite.lastIndexOf(':') + 1)) : null,
        },
    };
    if (record.unsupported) return { ...base, unsupported: record.unsupported };
    if (record.json.userConfig?.hooks) return { ...base, unsupported: 'config hooks' };

    const { json } = record;
    switch (record.kind) {
        case 'rule-processor':
            return {
                ...base,
                userConfig: json.userConfig,
                inputs: json.inputs,
                ...configPatch(json),
                options: json.options,
                expected: { classNames: json.classNames, css: json.css },
                ...(json.otherCalls.length ? { unsupported: `non-css calls (${json.otherCalls.join(', ')})` } : {}),
            };
        case 'parse-and-extract': {
            const others = [...new Set(json.extracted.filter((item) => item.type !== 'css').map((item) => item.type))];
            return {
                ...base,
                userConfig: json.userConfig,
                ...configPatch(json),
                inputs: json.extracted.filter((item) => item.type === 'css').flatMap((item) => item.data),
                expected: { css: json.css },
                ...(others.length ? { unsupported: `extracted non-css items (${others.join(', ')})` } : {}),
            };
        }
        case 'static-css': {
            const others = ['recipes', 'patterns', 'themes'].filter((key) => json.options?.[key]);
            return {
                ...base,
                ...configPatch(json),
                inputs: [json.options],
                expected: { styles: json.styles, css: json.css },
                ...(others.length ? { unsupported: `static css for ${others.join(', ')}` } : {}),
            };
        }
        case 'token-css':
            return {
                ...base,
                userConfig: json.userConfig,
                ...configPatch(json),
                inputs: [],
                expected: { css: json.css },
            };
        case 'codegen-css':
            return {
                ...base,
                inputs: json.inputs,
                expected: { className: json.className },
                ...(expectsTypeError(record.callSite) ? { expectTypeError: true } : {}),
                ...(record.undefinedValues ? { undefinedValues: true } : {}),
            };
        default:
            throw new Error(`Unknown record kind "${record.kind}".`);
    }
};

const writeFixtures = () => {
    const recordsDir = join(outDir, 'records');
    const records = readdirSync(recordsDir)
        .sort()
        .flatMap((file) =>
            readFileSync(join(recordsDir, file), 'utf8')
                .split('\n')
                .filter(Boolean)
                .map((line) => JSON.parse(line))
        );

    const byFile = new Map();
    const ordinals = new Map();
    for (const record of records) {
        const file = relative(pandaDir, record.testPath);
        const key = `${record.scenario}::${file}::${record.testName}`;
        const ordinal = (ordinals.get(key) ?? 0) + 1;
        ordinals.set(key, ordinal);
        const id = `${record.scenario}::${file}::${record.testName}::${ordinal}`;
        byFile.set(file, [...(byFile.get(file) ?? []), toCase(record, id)]);
    }

    mkdirSync(join(fixturesDir, 'cases'), { recursive: true });
    const written = new Set();
    for (const [file, cases] of [...byFile].sort(([a], [b]) => a.localeCompare(b))) {
        cases.sort((a, b) => a.id.localeCompare(b.id, 'en', { numeric: true }));
        const name = `${file.replaceAll('/', '.')}.json`;
        written.add(name);
        writeFileSync(join(fixturesDir, 'cases', name), JSON.stringify({ source: file, cases }, null, 2) + '\n');
    }
    cpSync(join(outDir, 'configs'), join(fixturesDir, 'configs'), { recursive: true });
    cpSync(join(outDir, 'utilities.json'), join(fixturesDir, 'utilities.json'));
    cpSync(join(outDir, 'tokens.json'), join(fixturesDir, 'tokens.json'));
    mkdirSync(join(toolDir, '../../resources'), { recursive: true });
    cpSync(join(outDir, 'panda-preset.json'), join(toolDir, '../../resources/panda-preset.json'));
    cpSync(join(outDir, 'panda-tokens.json'), join(toolDir, '../../resources/panda-tokens.json'));
    cpSync(join(outDir, 'css-types.json'), join(toolDir, '../../resources/css-types.json'));
    writeFileSync(join(fixturesDir, 'PANDA_COMMIT'), PANDA_COMMIT + '\n');

    console.log(
        `\nWrote ${records.length} cases from ${byFile.size} Panda test files into ${relative(process.cwd(), fixturesDir)}.`
    );
    const stale = readdirSync(join(fixturesDir, 'cases')).filter((name) => !written.has(name));
    if (stale.length) {
        console.log(
            `These case files no longer match a Panda test file, remove them with git rm:\n${stale.map((name) => `  cases/${name}`).join('\n')}`
        );
    }
};

checkoutPanda();
instrumentPanda();
recordFixtureSuite();
recordCodegenScenarios();
writeFixtures();
