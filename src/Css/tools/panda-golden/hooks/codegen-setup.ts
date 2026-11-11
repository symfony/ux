import { record } from './record';

(globalThis as any).__goldenCssHook = (styles: unknown[], className: string): void => {
    record('codegen-css', { inputs: styles, className });
};

(globalThis as any).__goldenCvaHook = (config: unknown, props: unknown, className: string): void => {
    record('codegen-cva', { config, props: props ?? null, className });
};
