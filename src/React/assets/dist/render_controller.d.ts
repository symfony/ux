import { type ReactElement } from 'react';
import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    readonly componentValue?: string;
    readonly propsValue?: object;
    readonly permanentValue: boolean;
    static values: {
        component: StringConstructor;
        props: ObjectConstructor;
        permanent: {
            type: BooleanConstructor;
            default: boolean;
        };
    };
    connect(): void;
    disconnect(): void;
    _renderReactElement(reactElement: ReactElement): void;
    private dispatchEvent;
}
