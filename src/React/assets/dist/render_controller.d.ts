import { Controller } from '@hotwired/stimulus';
import { type ReactElement } from 'react';
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
