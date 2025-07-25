import { Controller } from '@hotwired/stimulus';
import { ReactElement } from 'react';

declare class export_default extends Controller {
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

export { export_default as default };
