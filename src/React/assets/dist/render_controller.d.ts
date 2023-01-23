import { ReactElement } from 'react';
import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    readonly componentValue?: string;
    readonly propsValue?: object;
    static values: {
        component: StringConstructor;
        props: ObjectConstructor;
    };
    connect(): void;
    disconnect(): void;
    _renderReactElement(reactElement: ReactElement): void;
    private dispatchEvent;
}
