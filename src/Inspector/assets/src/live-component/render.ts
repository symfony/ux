import { makeField, makeElementField, makeGroup, makeKeyValueList } from '../ui/fields';
import type { ComponentData, RenderContext } from '../types';
import type { LiveData } from './plugin';
export function renderLive(data: ComponentData<LiveData>, context: RenderContext = {}): DocumentFragment {
    const frag = document.createDocumentFragment();
    const d = data.data;
    const runtime = d.runtime;
    const changes = context.changes as Map<string, { previous?: unknown }> | undefined;
    const props: HTMLElement[] = [];
    if (['updating', 'error', 'disconnected'].includes(runtime.status)) {
        props.push(makeField('status', runtime.status));
    }
    const modelBindings: HTMLElement[] = [];
    const actions: HTMLElement[] = [];
    const listeners: HTMLElement[] = [];
    const configuration: HTMLElement[] = [];

    const propEntries = Object.entries(d.props).filter(([key]) => !key.startsWith('@'));
    if (propEntries.length) {
        for (const [key, val] of propEntries) {
            const change = changes?.get(`props.${key}`);
            props.push(makeField(key, val, { changed: Boolean(change), previous: change?.previous }));
        }
    }

    const parentPropEntries = Object.entries(d.propsFromParent);
    if (parentPropEntries.length) {
        for (const [key, val] of parentPropEntries) {
            const change = changes?.get(`propsFromParent.${key}`);
            props.push(
                makeField(`${key} · from parent`, val, { changed: Boolean(change), previous: change?.previous })
            );
        }
    }

    if (d.models.length) {
        for (const model of d.models) {
            modelBindings.push(
                makeElementField(`${model.name} model`, model.element, {
                    framework: 'livecomponent',
                    badge: `model: ${model.name}`,
                    detail: model.modifiers.join(', '),
                })
            );
            if (!(model.name in d.props) && !(model.name in d.propsFromParent)) {
                props.push(makeField(model.name, model.value));
            }
        }
    }

    if (d.actions.length) {
        for (const action of d.actions) {
            actions.push(
                makeElementField(`${action.event} → ${action.method}()`, action.element, {
                    framework: 'livecomponent',
                    badge: `action: ${action.method}`,
                })
            );
            for (const [key, value] of Object.entries(action.args || {}))
                actions.push(makeField(`${action.method}.${key}`, value));
        }
    }

    if (d.listeners.length) {
        for (const listener of d.listeners) {
            listeners.push(makeField(listener.event, `${listener.action}()`));
        }
    }

    // Loading and polling describe component configuration, not relationships.
    if (d.loading.length) {
        for (const item of d.loading)
            configuration.push(
                makeElementField(`loading: ${item.action}`, item.element, {
                    framework: 'livecomponent',
                    badge: `loading: ${item.action}`,
                })
            );
    }

    if (d.polling) {
        configuration.push(makeField('poll interval', d.polling.duration));
    }

    const groups = [
        makeGroup('Props', [makeKeyValueList(props)], { key: 'livecomponent-props', icon: 'components' }),
        makeGroup('Models', [makeKeyValueList(modelBindings)], { key: 'livecomponent-models', icon: 'target' }),
        makeGroup('Configuration', [makeKeyValueList(configuration)], {
            key: 'livecomponent-configuration',
            icon: 'components',
        }),
        makeGroup('Actions', [makeKeyValueList(actions)], { key: 'livecomponent-actions', icon: 'activity' }),
        makeGroup('Listeners', [makeKeyValueList(listeners)], { key: 'livecomponent-listeners', icon: 'activity' }),
    ].filter(Boolean);
    if (groups.length) {
        const container = document.createElement('div');
        container.className = 'groups';
        container.append(...(groups as HTMLElement[]));
        frag.append(container);
    }

    return frag;
}
