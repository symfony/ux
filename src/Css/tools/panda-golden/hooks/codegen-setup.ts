import { record } from './record';

(globalThis as any).__goldenCssHook = (styles: unknown[], className: string): void => {
    record('codegen-css', { inputs: styles, className });
};
