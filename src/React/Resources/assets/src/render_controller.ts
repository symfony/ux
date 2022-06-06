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
    readonly propsValue: object;

    static values = {
        component: String,
        props: Object,
    };

    connect() {
        this._dispatchEvent('react:connect', { component: this.componentValue, props: this.propsValue });

        const component = window.resolveReactComponent(this.componentValue);
        this._renderReactElement(React.createElement(component, this.propsValue, null));

        this._dispatchEvent('react:mount', {
            componentName: this.componentValue,
            component: component,
            props: this.propsValue,
        });
    }

    disconnect() {
        (this.element as any).root.unmount();
        this._dispatchEvent('react:unmount', { component: this.componentValue, props: this.propsValue });
    }

    _renderReactElement(reactElement: ReactElement) {
        const root = createRoot(this.element);
        root.render(reactElement);

        (this.element as any).root = root;
    }

    _dispatchEvent(name: string, payload: any) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
    }
}
