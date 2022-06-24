/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import React, { ReactElement } from 'react';
import { createRoot } from 'react-dom/client';
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    readonly componentValue: string;
    readonly propsValue?: object;

    static values = {
        component: String,
        props: Object,
    };

    connect() {
        const props = this.propsValue ? this.propsValue : null;

        this._dispatchEvent('react:connect', { component: this.componentValue, props: props });

        const component = window.resolveReactComponent(this.componentValue);
        this._renderReactElement(React.createElement(component, props, null));

        this._dispatchEvent('react:mount', {
            componentName: this.componentValue,
            component: component,
            props: props,
        });
    }

    disconnect() {
        (this.element as any).root.unmount();
        this._dispatchEvent('react:unmount', {
            component: this.componentValue,
            props: this.propsValue ? this.propsValue : null,
        });
    }

    _renderReactElement(reactElement: ReactElement) {
        const element: any = this.element as any;

        // If a root has already been created for this element, reuse it
        if (!element.root) {
            element.root = createRoot(this.element);
        }

        element.root.render(reactElement);
    }

    _dispatchEvent(name: string, payload: any) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
    }
}
