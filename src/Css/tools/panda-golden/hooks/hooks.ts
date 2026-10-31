import { describeFunctions, toJson } from './config-json';
import { record } from './record';

const cssInputs = new WeakMap<object, unknown[]>();
const otherCalls = new WeakMap<object, string[]>();

const snapshot = (value: unknown): unknown => {
    try {
        return structuredClone(value);
    } catch {
        return value;
    }
};

export const goldenClone = (processor: object): void => {
    cssInputs.delete(processor);
    otherCalls.delete(processor);
};

export const goldenOtherCall = (processor: object, method: string): void => {
    otherCalls.set(processor, [...new Set([...(otherCalls.get(processor) ?? []), method])]);
};

export const goldenCssCall = (processor: object, styles: unknown): void => {
    cssInputs.set(processor, [...(cssInputs.get(processor) ?? []), snapshot(styles)]);
};

export const goldenToCss = (processor: any, options: unknown, css: string): string => {
    const inputs = cssInputs.get(processor);
    if (inputs) {
        record('rule-processor', {
            userConfig: describeFunctions(processor.context.__goldenUserConfig),
            resolvedConfig: processor.context.__goldenUserConfig ? toJson(processor.context.config) : null,
            inputs,
            otherCalls: otherCalls.get(processor) ?? [],
            options: options ?? null,
            classNames: Array.from(processor.decoder.classNames.keys()),
            css,
        });
    }
    return css;
};

export const goldenParseAndExtract = (
    userConfig: unknown,
    json: unknown,
    css: string,
    config: Record<string, unknown>
): void => {
    record('parse-and-extract', {
        userConfig: describeFunctions(userConfig),
        resolvedConfig: userConfig ? toJson(config) : null,
        extracted: json,
        css,
    });
};

export const goldenTokenCss = (userConfig: unknown, config: Record<string, unknown>, css: string): string => {
    record('token-css', {
        userConfig: describeFunctions(userConfig),
        resolvedConfig: userConfig ? toJson(config) : null,
        css,
    });
    return css;
};

export const goldenStaticCss = <T extends { results: { css: unknown[] }; css: string }>(
    options: unknown,
    config: Record<string, unknown>,
    output: T
): T => {
    record('static-css', {
        options: describeFunctions(options),
        resolvedConfig: toJson(config),
        styles: output.results.css,
        css: output.css,
    });
    return output;
};
