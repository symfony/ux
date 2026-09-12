import { safeValue } from '../core/snapshot';
import type { EventRecorder } from '../types';

export interface RuntimeInfo {
    status: string;
    duration: number | null;
    httpStatus: number | null;
    error: string | null;
    startedAt?: number;
}

type LiveRequest = { actions?: Array<{ name?: string }>; updated?: object; files?: object };
type LiveErrorResponse = { response?: { status?: number } };

/** Only the arguments consumed by the inspector; the runtime may supply more. */
interface LiveHooks {
    'request:started': [request: LiveRequest];
    'model:set': [model: string, value: unknown];
    'render:started': [];
    'render:finished': [];
    'response:error': [response: LiveErrorResponse];
}

interface LiveRuntimeComponent {
    on<K extends keyof LiveHooks>(event: K, handler: (...args: LiveHooks[K]) => void): void;
    off?<K extends keyof LiveHooks>(event: K, handler: (...args: LiveHooks[K]) => void): void;
}

type LiveElement = Element & { __component?: LiveRuntimeComponent };
interface Subscription {
    component: LiveRuntimeComponent;
    cleanups: Array<() => void>;
}

const IDLE_RUNTIME: RuntimeInfo = { status: 'detected', duration: null, httpStatus: null, error: null };

/** Owns subscriptions to LiveComponent runtime objects; reading a snapshot never subscribes. */
export class LiveObserver {
    #record: EventRecorder | null = null;
    #subscriptions = new Map<Element, Subscription>();
    #runtime = new WeakMap<Element, RuntimeInfo>();

    setEventRecorder(record: EventRecorder): void {
        this.#record = record;
    }

    read(element: Element): RuntimeInfo {
        return this.#runtime.get(element) ?? { ...IDLE_RUNTIME };
    }

    connected(element: Element): void {
        this.observe(element);
        this.#runtime.set(element, { ...IDLE_RUNTIME, status: 'connected' });
    }

    disconnected(element: Element): void {
        this.remove(element);
        this.#runtime.set(element, { ...IDLE_RUNTIME, status: 'disconnected' });
    }

    remove(element: Element): void {
        const subscription = this.#subscriptions.get(element);
        if (!subscription) return;
        // Invalidate callbacks before asking the application to remove them.
        this.#subscriptions.delete(element);
        for (const cleanup of subscription.cleanups) {
            try {
                cleanup();
            } catch (error) {
                this.#warn(error);
            }
        }
    }

    destroy(): void {
        for (const element of this.#subscriptions.keys()) this.remove(element);
        this.#record = null;
    }

    observe(element: LiveElement): void {
        const component = element.__component;
        if (this.#subscriptions.get(element)?.component === component) return;
        this.remove(element);
        if (typeof component?.on !== 'function') return;
        this.#runtime.set(element, { ...IDLE_RUNTIME, status: 'connected' });
        if (!this.#record) return;
        const subscription: Subscription = { component, cleanups: [] };
        this.#subscriptions.set(element, subscription);

        const on = <K extends keyof LiveHooks>(event: K, callback: (...args: LiveHooks[K]) => void) => {
            const handler = (...args: LiveHooks[K]) => {
                if (this.#subscriptions.get(element) !== subscription || element.__component !== component) return;
                // Diagnostic failures must never interrupt a LiveComponent request or render.
                try {
                    callback(...args);
                } catch (error) {
                    this.#warn(error);
                }
            };
            subscription.cleanups.push(() => component.off?.(event, handler));
            component.on(event, handler);
        };
        try {
            on('request:started', (request) => this.#requestStarted(element, request));
            on('model:set', (model, value) => {
                const name = String(model).slice(0, 200);
                this.#emit(element, 'model:set', { model: name, value: safeValue(value, name) }, `model: ${name}`);
            });
            on('render:started', () => this.#emit(element, 'render:started'));
            on('render:finished', () => this.#renderFinished(element));
            on('response:error', (response) => this.#responseError(element, response));
        } catch (error) {
            this.remove(element);
            this.#warn(error);
        }
    }

    #requestStarted(element: Element, request: LiveRequest): void {
        this.#runtime.set(element, { ...IDLE_RUNTIME, status: 'updating', startedAt: performance.now() });
        const actions = (request?.actions ?? []).flatMap((action) =>
            action?.name ? [String(action.name).slice(0, 100)] : []
        );
        const models = Object.keys(request?.updated ?? {});
        const files = Object.keys(request?.files ?? {});
        const parameters: Record<string, unknown> = {};
        if (actions.length) parameters.actions = actions;
        if (models.length) parameters.models = models;
        if (files.length) parameters.files = files;
        this.#emit(
            element,
            'request',
            Object.keys(parameters).length ? parameters : null,
            actions.length ? `request: ${actions.join(', ')}` : 'request: render'
        );
    }

    #renderFinished(element: Element): void {
        const runtime = this.read(element);
        this.#runtime.set(element, {
            ...runtime,
            status: 'idle',
            duration: runtime.startedAt === undefined ? null : performance.now() - runtime.startedAt,
            error: null,
        });
        this.#emit(element, 'render:finished');
    }

    #responseError(element: Element, response: LiveErrorResponse): void {
        const status = response?.response?.status ?? null;
        this.#runtime.set(element, {
            ...this.read(element),
            status: 'error',
            httpStatus: status,
            error: status ? `Request failed (${status})` : 'Request failed',
        });
        this.#emit(element, 'response:error', { status });
    }

    #emit(element: Element, event: string, detail: unknown = null, label = event): void {
        const name = (element.getAttribute('data-live-name-value') || '').slice(0, 200) || 'LiveComponent';
        this.#record?.({
            type: 'livecomponent',
            event: `live:${event}`,
            target: element,
            detail,
            label: `${name}: ${label}`,
        });
    }

    #warn(error: unknown): void {
        console.warn('[ux-inspector] LiveComponent observation failed:', error);
    }
}
