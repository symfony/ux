/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from '@hotwired/stimulus';
import { type App, createApp } from 'vue';

export default class extends Controller<Element & { __vue_app__?: App<Element> }> {
    private props: Record<string, unknown> | null;
    private app: App<Element>;
    declare readonly componentValue: string;
    declare readonly propsValue: Record<string, unknown> | null | undefined;

    static values = {
        component: String,
        props: Object,
    };

    connect() {
        this.props = this.propsValue ?? null;

        this.dispatchEvent('connect', { componentName: this.componentValue, props: this.props });

        const component = window.resolveVueComponent(this.componentValue);

        this.app = createApp(component, this.props);

        if (this.element.__vue_app__ !== undefined) {
            this.element.__vue_app__.unmount();
        }

        this.dispatchEvent('before-mount', {
            componentName: this.componentValue,
            component: component,
            props: this.props,
            app: this.app,
        });

        this.app.mount(this.element);

        this.dispatchEvent('mount', {
            componentName: this.componentValue,
            component: component,
            props: this.props,
        });
    }

    disconnect() {
        this.app.unmount();

        this.dispatchEvent('unmount', {
            componentName: this.componentValue,
            props: this.props,
        });
    }

    private dispatchEvent(name: string, payload: any) {
        this.dispatch(name, { detail: payload, prefix: 'vue' });
    }
}
