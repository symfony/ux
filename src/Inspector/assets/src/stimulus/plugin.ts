import { ancestors, scopedChildren, queryElements, sameElements, type QueryElements } from '../core/dom-query';
import { parseActionDescriptor, parseActionParameters, parseAttributeValue } from './attributes';
import { safeValue } from '../core/snapshot';
import type { ComponentData, InspectorPlugin, MonitoredEvents, RelationshipEdge } from '../types';

const CONNECTED = 'connected';
const MISSING = 'missing';
const CONFIGURED = 'configured';
const DOM_ONLY = 'dom-only';

export interface StimulusApplicationLike {
    getControllerForElementAndIdentifier(element: Element, identifier: string): object | null;
}

type ControllerInstance = object | null;
type Instances = Record<string, ControllerInstance>;
type Bag = Record<string, unknown>;

interface ValueState {
    name: string;
    value: unknown;
    status: string;
}

interface ClassState {
    name: string;
    value: string;
    status: string;
}

interface TargetItem {
    name: string;
    elements: Element[];
    declared: boolean;
    status: string;
}

interface TargetGroup {
    elements: Array<{ name: string; element: Element }>;
    items: TargetItem[];
}

interface ActionBinding {
    event: string;
    method: string;
    element: Element;
    status: string;
    scope?: string;
    filters?: string[];
    options?: string[];
    params?: Bag;
}

interface OutletBinding {
    name: string;
    selector: string;
    elements: Element[];
    declared: boolean;
    status: string;
}

interface ControllerRef {
    element: Element;
    controllers: string[];
}

export interface StimulusData {
    controllers: string[];
    runtimeAvailable: boolean;
    connectedControllers: string[];
    values: Record<string, Bag>;
    valueStates: Record<string, ValueState[]>;
    targets: Record<string, TargetGroup>;
    actions: Record<string, ActionBinding[]>;
    classes: Record<string, Record<string, string>>;
    classStates: Record<string, ClassState[]>;
    outlets: Record<string, OutletBinding[]>;
    children: ControllerRef[];
    parents: ControllerRef[];
    [key: string]: unknown;
}

export class StimulusPlugin implements InspectorPlugin<StimulusData> {
    name = 'stimulus';
    selectors = ['[data-controller]'];
    #application: StimulusApplicationLike | null = null;

    constructor(application: StimulusApplicationLike | null = null) {
        this.setApplication(application);
    }

    setApplication(application: StimulusApplicationLike | null): void {
        this.#application = application?.getControllerForElementAndIdentifier ? application : null;
    }

    canHandle(element: Element): boolean {
        return this.#parseControllers(element).length > 0;
    }

    parse(element: Element, query = queryElements): ComponentData<StimulusData> {
        const reference = (element: Element): ControllerRef => ({
            element,
            controllers: this.#parseControllers(element),
        });
        const controllers = this.#parseControllers(element);
        const instances = Object.fromEntries(
            controllers.map((identifier) => [identifier, this.#getController(element, identifier)])
        );
        const values = this.#parseControllerAttrs(element, 'value', parseAttributeValue);
        const classes = this.#parseClasses(element);
        return {
            type: 'stimulus',
            element,
            data: {
                controllers,
                runtimeAvailable: Boolean(this.#application),
                connectedControllers: controllers.filter((identifier) => instances[identifier]),
                values,
                valueStates: this.#resolveValueStates(element, controllers, instances, values),
                targets: this.#resolveTargets(element, controllers, instances),
                actions: this.#resolveActions(element, controllers, instances),
                classes,
                classStates: this.#resolveClassStates(controllers, instances, classes),
                outlets: this.#resolveOutlets(element, controllers, instances, query),
                children: scopedChildren(element, '[data-controller]').map(reference),
                parents: ancestors(element, '[data-controller]').map(reference),
            },
        };
    }

    getDisplayName(element: Element): string {
        const controllers = this.#parseControllers(element);
        if (controllers.length > 1) {
            return `${controllers[0]} (+${controllers.length - 1})`;
        }
        return controllers[0] || 'Stimulus';
    }

    getRelationships(element: Element, data: ComponentData<StimulusData>): RelationshipEdge[] {
        const edges: RelationshipEdge[] = [];
        const d = data.data;

        for (const controller of d.controllers) {
            const outlets = d.outlets[controller];
            if (!outlets) continue;
            for (const outlet of outlets) {
                for (const target of outlet.elements) {
                    if (target?.isConnected) {
                        edges.push({
                            source: element,
                            target,
                            type: 'outlet',
                            label: `${controller} -> ${outlet.name}`,
                        });
                    }
                }
            }
        }

        for (const child of d.children) {
            if (child.element?.isConnected) {
                edges.push({
                    source: element,
                    target: child.element,
                    type: 'controller-parent',
                    label: `parent of ${child.controllers.join(', ')}`,
                });
            }
        }

        return edges;
    }

    getMonitoredEvents(): MonitoredEvents {
        /** Common event names that Stimulus controllers dispatch via this.dispatch(). */
        const COMMON_DISPATCH_NAMES = [
            'open',
            'close',
            'toggle',
            'expand',
            'collapse',
            'navigate',
            'change',
            'submit',
            'submitted',
            'clear',
            'search',
            'perform',
            'copied',
            'selected',
            'removed',
            'added',
            'show',
            'hide',
            'activate',
            'deactivate',
            'connect',
            'disconnect',
            'refresh',
        ];

        return {
            static: ['stimulus:connect', 'stimulus:disconnect'],
            dynamic(elements: Element[]) {
                const controllers = new Set<string>();
                const actionEvents = new Set<string>();

                for (const el of elements) {
                    for (const name of (el.getAttribute('data-controller') || '').split(/\s+/).filter(Boolean)) {
                        if (name !== 'live') controllers.add(name);
                    }
                }

                for (const el of elements) {
                    const walk = el.querySelectorAll('[data-action]');
                    for (const target of [el, ...walk]) {
                        const raw = target.getAttribute('data-action');
                        if (!raw) continue;
                        for (const descriptor of raw.split(/\s+/).filter(Boolean)) {
                            const match = descriptor.match(/^([^->\s]+)->/) || descriptor.match(/^([^:]+:[^->\s]+)/);
                            if (match) {
                                const eventPart = match[1];
                                if (eventPart.includes(':')) {
                                    const prefix = eventPart.split(':')[0];
                                    if (controllers.has(prefix)) {
                                        actionEvents.add(eventPart);
                                    }
                                }
                            }
                        }
                    }
                }

                // 2. Register common dispatch patterns for each controller.
                //    Stimulus this.dispatch('name') emits "controller:name" as a
                //    CustomEvent. We can't statically know what a controller dispatches,
                //    so we register well-known patterns to catch the majority of events.
                for (const name of controllers) {
                    for (const suffix of COMMON_DISPATCH_NAMES) {
                        actionEvents.add(`${name}:${suffix}`);
                    }
                }

                return [...actionEvents];
            },
        };
    }

    matchesAttribute(name: string): boolean {
        return (
            ['id', 'class', 'data-controller', 'data-action'].includes(name) ||
            /^data-.+-(value|target|class|outlet|param)$/.test(name)
        );
    }

    hasExternalChanges(_element: Element, { data }: ComponentData<StimulusData>, query: QueryElements): boolean {
        for (const [controller, outlets] of Object.entries(data.outlets)) {
            for (const outlet of outlets) {
                if (!outlet.selector) continue;
                const matches = query(outlet.selector);
                const elements = data.connectedControllers.includes(controller)
                    ? matches.filter((candidate) =>
                          (candidate.getAttribute('data-controller') ?? '').split(/\s+/).includes(outlet.name)
                      )
                    : matches;
                if (!sameElements(outlet.elements, elements)) return true;
            }
        }
        return false;
    }

    #parseControllers(element: Element): string[] {
        return (element.getAttribute('data-controller') || '')
            .split(/\s+/)
            .filter((controller) => controller && controller !== 'live');
    }

    #getController(element: Element, identifier: string): ControllerInstance {
        if (!this.#application) return null;
        try {
            return this.#application.getControllerForElementAndIdentifier(element, identifier) || null;
        } catch {
            return null;
        }
    }

    #staticDefinition(instance: ControllerInstance, property: string, fallback: unknown): unknown {
        const prototype = instance && Object.getPrototypeOf(instance);
        const ownConstructor = prototype && Object.getOwnPropertyDescriptor(prototype, 'constructor');
        let constructor = ownConstructor && 'value' in ownConstructor ? ownConstructor.value : null;
        while (constructor && constructor !== Function.prototype) {
            const descriptor = Object.getOwnPropertyDescriptor(constructor, property);
            if (descriptor) return 'value' in descriptor ? descriptor.value : fallback;
            constructor = Object.getPrototypeOf(constructor);
        }
        return fallback;
    }

    #hasMethod(instance: ControllerInstance, method: string): boolean {
        let current: object | null = instance;
        while (current) {
            const descriptor = Object.getOwnPropertyDescriptor(current, method);
            if (descriptor) return 'value' in descriptor && typeof descriptor.value === 'function';
            current = Object.getPrototypeOf(current);
        }
        return false;
    }

    #resolveValueStates(
        element: Element,
        controllers: string[],
        instances: Instances,
        configured: Record<string, Bag>
    ): Record<string, ValueState[]> {
        const result: Record<string, ValueState[]> = {};
        for (const controller of controllers) {
            const current = configured[controller] || {};
            const definitions = (this.#staticDefinition(instances[controller], 'values', {}) ?? {}) as Bag;
            const names = new Set([...Object.keys(definitions), ...Object.keys(current)]);
            result[controller] = [...names].map((name) => {
                const attribute = `data-${controller}-${name.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`)}-value`;
                const present = element.hasAttribute(attribute);
                let value = current[name];
                const definition = Object.getOwnPropertyDescriptor(definitions, name);
                if (!present)
                    value = safeValue(
                        this.#valueDefault(definition && 'value' in definition ? definition.value : undefined),
                        name
                    );
                return {
                    name,
                    value,
                    status: instances[controller] ? (present ? CONNECTED : 'default') : CONFIGURED,
                };
            });
        }
        return result;
    }

    #valueDefault(definition: unknown): unknown {
        let type: unknown = definition;
        if (definition && typeof definition === 'object') {
            const value = Object.getOwnPropertyDescriptor(definition, 'default');
            if (value && 'value' in value) return value.value;
            const declaredType = Object.getOwnPropertyDescriptor(definition, 'type');
            type = declaredType && 'value' in declaredType ? declaredType.value : null;
        }
        if (type === Array) return [];
        if (type === Boolean) return false;
        if (type === Number) return 0;
        if (type === Object) return {};
        if (type === String) return '';
        return undefined;
    }

    #resolveClassStates(
        controllers: string[],
        instances: Instances,
        configured: Record<string, Record<string, string>>
    ): Record<string, ClassState[]> {
        const result: Record<string, ClassState[]> = {};
        for (const controller of controllers) {
            const current = configured[controller] || {};
            const declared = this.#staticDefinition(instances[controller], 'classes', []);
            const names = new Set([...(Array.isArray(declared) ? declared : []), ...Object.keys(current)]);
            result[controller] = [...names].map((name) => ({
                name,
                value: current[name] || 'not configured',
                status: current[name] ? (instances[controller] ? CONNECTED : CONFIGURED) : MISSING,
            }));
        }
        return result;
    }

    #parseControllerAttrs<T>(
        element: Element,
        suffix: string,
        transform: (value: string, key: string) => T
    ): Record<string, Record<string, T>> {
        const result: Record<string, Record<string, T>> = {};
        for (const controller of this.#parseControllers(element)) {
            const prefix = `data-${controller}-`;
            for (const attr of element.attributes) {
                if (!attr.name.startsWith(prefix) || !attr.name.endsWith(`-${suffix}`)) continue;
                const name = attr.name.slice(prefix.length, -suffix.length - 1);
                if (!name) continue;
                const key = name.replace(/(?:[_-])([a-z0-9])/g, (_, letter: string) => letter.toUpperCase());
                (result[controller] ??= {})[key] = transform(attr.value, key);
            }
        }
        return result;
    }

    #resolveTargets(element: Element, controllers: string[], instances: Instances = {}): Record<string, TargetGroup> {
        const result: Record<string, TargetGroup> = {};
        for (const controller of controllers) {
            const scope = element;
            const elements: Array<{ name: string; element: Element }> = [];

            const attr = `data-${controller}-target`;
            const candidates = scope.querySelectorAll(`[${attr}]`);

            for (const candidate of candidates) {
                // Only include if within this controller's scope (not a nested controller's scope)
                if (this.#isInScope(candidate, element, controller)) {
                    const names = (candidate.getAttribute(attr) ?? '').split(/\s+/).filter(Boolean);
                    for (const name of names) {
                        elements.push({ name, element: candidate });
                    }
                }
            }

            if (element.hasAttribute(attr)) {
                const names = (element.getAttribute(attr) ?? '').split(/\s+/).filter(Boolean);
                for (const name of names) {
                    elements.push({ name, element });
                }
            }

            const declared = this.#staticDefinition(instances[controller], 'targets', []);
            const names = new Set([...(Array.isArray(declared) ? declared : []), ...elements.map((item) => item.name)]);
            if (!names.size) continue;
            result[controller] = {
                elements,
                items: [...names].map((name) => {
                    const matches = elements.filter((item) => item.name === name).map((item) => item.element);
                    return {
                        name,
                        elements: matches,
                        declared: Array.isArray(declared) && declared.includes(name),
                        status: instances[controller]
                            ? matches.length
                                ? CONNECTED
                                : MISSING
                            : matches.length
                              ? DOM_ONLY
                              : MISSING,
                    };
                }),
            };
        }
        return result;
    }

    #resolveActions(
        element: Element,
        controllers: string[],
        instances: Instances = {}
    ): Record<string, ActionBinding[]> {
        const result: Record<string, ActionBinding[]> = Object.fromEntries(
            controllers.map((c) => [c, [] as ActionBinding[]])
        );

        const allElements = [element, ...element.querySelectorAll('[data-action]')];

        for (const candidate of allElements) {
            const raw = candidate.getAttribute('data-action');
            if (!raw) continue;

            const descriptors = raw.split(/\s+/).filter(Boolean);
            for (const descriptor of descriptors) {
                const parsed = parseActionDescriptor(descriptor);
                if (parsed && controllers.includes(parsed.controller)) {
                    if (this.#isInScope(candidate, element, parsed.controller)) {
                        const action: ActionBinding = {
                            event: parsed.event || this.#defaultActionEvent(candidate),
                            method: parsed.method,
                            element: candidate,
                            status: instances[parsed.controller]
                                ? this.#hasMethod(instances[parsed.controller], parsed.method)
                                    ? CONNECTED
                                    : 'missing-method'
                                : DOM_ONLY,
                        };
                        const params = parseActionParameters(candidate, parsed.controller);
                        if (parsed.scope) action.scope = parsed.scope;
                        if (parsed.filters.length) action.filters = parsed.filters;
                        if (parsed.options.length) action.options = parsed.options;
                        if (Object.keys(params).length) action.params = params;
                        result[parsed.controller].push(action);
                    }
                }
            }
        }

        return result;
    }

    #defaultActionEvent(element: Element): string {
        return (
            (
                {
                    BUTTON: 'click',
                    FORM: 'submit',
                    INPUT: 'input',
                    TEXTAREA: 'input',
                    SELECT: 'change',
                    DETAILS: 'toggle',
                } as Record<string, string>
            )[element.tagName] || 'default'
        );
    }

    /**
     * Resolve outlets: find outlet declarations and their matching elements.
     * data-{controller}-{outlet-name}-outlet="selector"
     * Returns { [controller]: [{name, selector, elements}] }
     */
    #resolveOutlets(
        element: Element,
        controllers: string[],
        instances: Instances,
        query: QueryElements
    ): Record<string, OutletBinding[]> {
        const result: Record<string, OutletBinding[]> = {};
        for (const controller of controllers) {
            const configured = new Map<string, { name: string; selector: string; elements: Element[] }>();
            for (const attr of element.attributes) {
                const match = attr.name.match(new RegExp(`^data-${controller}-(.+)-outlet$`));
                if (match) {
                    const name = match[1];
                    const selector = attr.value;
                    const elements = [...query(selector)];
                    configured.set(name, { name, selector, elements });
                }
            }
            const declared = this.#staticDefinition(instances[controller], 'outlets', []);
            const names = new Set([...(Array.isArray(declared) ? declared : []), ...configured.keys()]);
            if (!names.size) continue;
            result[controller] = [...names].map((name) => {
                const item = configured.get(name) || { name, selector: '', elements: [] };
                const elements = instances[controller]
                    ? item.elements.filter((candidate) =>
                          (candidate.getAttribute('data-controller') || '').split(/\s+/).includes(name)
                      )
                    : item.elements;
                return {
                    ...item,
                    elements,
                    declared: Array.isArray(declared) && declared.includes(name),
                    status: instances[controller]
                        ? !item.selector || !elements.length
                            ? MISSING
                            : CONNECTED
                        : item.selector
                          ? CONFIGURED
                          : MISSING,
                };
            });
        }
        return result;
    }

    #parseClasses(element: Element): Record<string, Record<string, string>> {
        return this.#parseControllerAttrs(element, 'class', (v) => v);
    }

    /**
     * Check if a candidate element is within the scope of a controller rooted at `root`.
     * An element is out of scope if there's a closer ancestor with the same controller.
     */
    #isInScope(candidate: Element, root: Element, controller: string): boolean {
        if (candidate === root) return true;
        let parent: Element | null = candidate.parentElement;
        while (parent && parent !== root) {
            if (parent.hasAttribute('data-controller')) {
                const parentControllers = this.#parseControllers(parent);
                if (parentControllers.includes(controller)) {
                    return false; // Belongs to a nested instance of the same controller
                }
            }
            parent = parent.parentElement;
        }
        return parent === root;
    }
}
