import type { Directive } from './directives_parser';
export interface ModelBinding {
    modelName: string;
    innerModelName: string | null;
    shouldRender: boolean;
    debounce: number | boolean;
    targetEventName: string | null;
}
export default function (modelDirective: Directive): ModelBinding;
