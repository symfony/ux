/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from '@hotwired/stimulus';
import {
    type App,
    type Component,
    createApp,
    defineComponent,
    h,
    type ShallowReactive,
    shallowReactive,
    toRaw,
    watch,
} from 'vue';

export default class extends Controller<Element & { __vue_app__?: App<Element> }> {
    private props: ShallowReactive<Record<string, unknown>>;
    private app: App<Element>;
    declare readonly componentValue: string;
    declare readonly hasPropsValue: boolean;
    declare propsValue: Record<string, unknown> | null | undefined;

    static values = {
        component: String,
        props: Object,
    };

    propsValueChanged(newProps: typeof this.propsValue, oldProps: typeof this.propsValue) {
        if (oldProps) {
            let removedPropNames = Object.keys(oldProps);

            if (newProps) {
                removedPropNames = removedPropNames.filter(
                    (propName) => !Object.prototype.hasOwnProperty.call(newProps, propName)
                );
            }

            removedPropNames.forEach((propName) => {
                delete this.props[propName];
            });
        }
        if (newProps) {
            Object.entries(newProps).forEach(([propName, propValue]) => {
                this.props[propName] = propValue;
            });
        }
    }

    initialize() {
        const props = this.hasPropsValue && this.propsValue ? this.propsValue : {};
        this.props = shallowReactive({ ...props });
        watch(
            this.props,
            (props) => {
                this.propsValue = toRaw(props);
            },
            { flush: 'post' }
        );
    }

    connect() {
        this.dispatchEvent('connect', { componentName: this.componentValue, props: this.props });

        const component = window.resolveVueComponent(this.componentValue);
        const wrappedComponent = this.wrapComponent(component);

        this.app = createApp(wrappedComponent);

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

    private wrapComponent(component: Component): Component {
        return defineComponent({
            setup: () => {
                const props = this.props;

                return () =>
                    h(component, {
                        ...props,
                        ...Object.fromEntries(
                            Object.keys(props).map((propName) => [
                                `onUpdate:${propName}`,
                                (value: unknown) => {
                                    props[propName] = value;
                                },
                            ])
                        ),
                    });
            },
        });
    }
}
