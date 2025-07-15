import type { Directive } from './directives_parser';
export interface ModelBinding {
    modelName: string;
    innerModelName: string | null;
    shouldRender: boolean;
    debounce: number | boolean;
    targetEventName: string | null;
    minLength: number | null;
    maxLength: number | null;
    minValue: number | null;
    maxValue: number | null;
}
export default function (modelDirective: Directive): ModelBinding;
