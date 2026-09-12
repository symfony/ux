import { makeField, makeElementField, makeGroup, makeKeyValueList } from '../ui/fields';
import type { ComponentData, RenderContext } from '../types';
import type { StimulusData } from './plugin';
const CONNECTED = 'connected';
const DOM_ONLY = 'dom-only';
export function renderStimulus(data: ComponentData<StimulusData>, context: RenderContext = {}): DocumentFragment {
    const frag = document.createDocumentFragment();
    const d = data.data;
    const changes = context.changes as Map<string, { previous?: unknown }> | undefined;
    const controllerStates: HTMLElement[] = [];
    const values: HTMLElement[] = [];
    const targets: HTMLElement[] = [];
    const actions: HTMLElement[] = [];
    const classes: HTMLElement[] = [];
    const unresolvedOutlets: HTMLElement[] = [];
    const qualify =
        d.controllers.length > 1
            ? (controller: string, key: string) => `${controller}.${key}`
            : (_controller: string, key: string) => key;
    for (const controller of d.controllers) {
        const connected = d.connectedControllers?.includes(controller);
        const runtimeAvailable = d.runtimeAvailable;
        if (!connected) {
            controllerStates.push(
                makeField(
                    d.controllers.length > 1 ? `${controller} status` : 'status',
                    runtimeAvailable ? 'inactive' : 'runtime unavailable',
                    {
                        status: runtimeAvailable ? 'not-connected' : DOM_ONLY,
                    }
                )
            );
        }
        const controllerValues = d.valueStates?.[controller] || [];
        for (const { name: key, value: val, status } of controllerValues) {
            const change = changes?.get(`values.${controller}.${key}`);
            values.push(
                makeField(qualify(controller, key), val, {
                    changed: Boolean(change),
                    previous: change?.previous,
                    status,
                })
            );
        }
        const controllerClasses = d.classStates?.[controller] || [];
        for (const item of controllerClasses)
            classes.push(makeField(qualify(controller, item.name), item.value, { status: item.status }));
        const controllerTargets = d.targets[controller];
        const targetItems = controllerTargets?.items || [];
        for (const target of targetItems) {
            if (!target.elements.length) continue;
            for (const [index, element] of target.elements.entries()) {
                const suffix = target.elements.length > 1 ? `[${index + 1}]` : '';
                targets.push(
                    makeElementField(`${qualify(controller, target.name)}${suffix}`, element, {
                        framework: 'stimulus',
                        badge: `${controller}.${target.name} target`,
                        detail: target.status === CONNECTED ? null : target.status?.replaceAll('-', ' '),
                    })
                );
            }
        }
        const controllerActions = d.actions[controller];
        if (controllerActions?.length) {
            for (const action of controllerActions) {
                const trigger = `${action.event}${(action.filters || []).map((filter) => `.${filter}`).join('')}${action.scope ? `@${action.scope}` : ''}${(action.options || []).map((option) => `:${option}`).join('')}`;
                actions.push(
                    makeElementField(`${qualify(controller, action.method)}()`, action.element, {
                        framework: 'stimulus',
                        badge: `${controller}#${action.method}`,
                        detail: [trigger, action.status === CONNECTED ? null : action.status?.replaceAll('-', ' ')]
                            .filter(Boolean)
                            .join(' · '),
                        parameters: action.params,
                    })
                );
            }
        }
        const controllerOutlets = d.outlets[controller];
        if (controllerOutlets?.length) {
            for (const outlet of controllerOutlets) {
                if (!outlet.elements.length) {
                    unresolvedOutlets.push(
                        makeField(`${qualify(controller, outlet.name)} outlet`, outlet.selector || 'not configured', {
                            status: outlet.status,
                            multiline: outlet.selector?.includes(','),
                        })
                    );
                }
            }
        }
    }
    const groups = [
        makeGroup('Values', [makeKeyValueList([...values, ...controllerStates])], {
            key: 'stimulus-values',
            icon: 'components',
        }),
        makeGroup('Classes', [makeKeyValueList(classes)], { key: 'stimulus-classes', icon: 'components' }),
        makeGroup('Actions', actions, { key: 'stimulus-actions', icon: 'activity' }),
        makeGroup('Targets', [makeKeyValueList(targets)], { key: 'stimulus-targets', icon: 'target' }),
        makeGroup('Outlets', [makeKeyValueList(unresolvedOutlets)], { key: 'stimulus-outlets', icon: 'overlay' }),
    ].filter(Boolean);
    if (groups.length) {
        const container = document.createElement('div');
        container.className = 'groups';
        container.append(...(groups as HTMLElement[]));
        frag.append(container);
    }
    return frag;
}
