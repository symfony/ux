import { preset } from '@pandacss/preset-base';

const PRESET_TRANSFORMS = new Set(Object.values(preset.utilities ?? {}).map((utility) => utility?.transform));

const IRRELEVANT_KEYS = [
    'presets',
    'patterns',
    'globalCss',
    'include',
    'exclude',
    'cwd',
    'jsxFramework',
    'name',
    'preflight',
    'plugins',
    'hooks',
];

const probeTheme = (category: string) => ({ [`__category:${category}`]: category });

const probeColorMix = (transform: (value: string, args: unknown) => unknown): string | undefined => {
    try {
        const styles = transform('__value__', {
            raw: '__value__',
            token: Object.assign(() => undefined, { raw: () => undefined }),
            utils: { colorMix: () => ({ invalid: false, value: '__mix__', color: '__color__' }) },
        }) as Record<string, string>;
        const keys = Object.keys(styles ?? {});
        const [mixVar, property] = keys;
        if (keys.length === 2 && mixVar === `--mix-${property}` && styles[mixVar] === '__mix__') return property;
    } catch {
        return undefined;
    }
    return undefined;
};

// Function values are spreads of theme(category) plus static entries: probing them keeps that shape as data.
const describeUtilities = (utilities: Record<string, Record<string, unknown>>) =>
    Object.fromEntries(
        Object.entries(utilities ?? {}).map(([name, utility]) => {
            const described: Record<string, unknown> = { ...utility };
            if (typeof utility.values === 'function') {
                described.values = { __function: 'values', probe: utility.values(probeTheme) };
            }
            if (typeof utility.transform === 'function') {
                const colorMix = probeColorMix(utility.transform as (value: string, args: unknown) => unknown);
                if (colorMix) described.transform = { __function: 'transform', colorMix };
                else if (PRESET_TRANSFORMS.has(utility.transform)) described.transform = { __function: 'transform' };
                else described.transform = { __function: 'transform', custom: true };
            }
            return [name, described];
        })
    );

export const toJson = (config: Record<string, unknown>): unknown =>
    JSON.parse(
        JSON.stringify(
            Object.fromEntries(
                Object.entries({ ...config, utilities: describeUtilities(config.utilities as never) }).filter(
                    ([key]) => !IRRELEVANT_KEYS.includes(key)
                )
            ),
            (_key, v) => {
                if (typeof v === 'function') return { __function: v.name || 'anonymous' };
                if (v instanceof Map) return Object.fromEntries(v);
                if (v instanceof Set) return Array.from(v);
                return v;
            }
        )
    );

export const describeFunctions = (value: unknown): unknown =>
    JSON.parse(
        JSON.stringify(value ?? null, (_key, v) =>
            typeof v === 'function' ? { __function: v.name || 'anonymous' } : v
        )
    );
