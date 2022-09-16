/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Controller } from '@hotwired/stimulus';
import { App, createApp } from 'vue';

export default class extends Controller<Element & { __vue_app__?: App<Element> }> {
    private props: Record<string, unknown> | null;
    private app: App<Element>;
    readonly componentValue: string;

    readonly propsValue: Record<string, unknown> | null | undefined;
    static values = {
        component: String,
        props: Object,
    };

    connect() {
        this.props = this.propsValue ?? null;

        this._dispatchEvent('vue:connect', { componentName: this.componentValue, props: this.props });

        const component = window.resolveVueComponent(this.componentValue);

        this.app = createApp(component, this.props);

        if (this.element.__vue_app__ !== undefined) {
            this.element.__vue_app__.unmount();
        }
        
        this._dispatchEvent('vue:before-mount', {
            componentName: this.componentValue,
            component: component,
            props: this.props,
            app: this.app,
        });

        this.app.mount(this.element);

        this._dispatchEvent('vue:mount', {
            componentName: this.componentValue,
            component: component,
            props: this.props,
        });
    }

    disconnect() {
        this.app.unmount();

        this._dispatchEvent('vue:unmount', {
            componentName: this.componentValue,
            props: this.props,
        });
    }

    _dispatchEvent(name: string, payload: any) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
    }
}
