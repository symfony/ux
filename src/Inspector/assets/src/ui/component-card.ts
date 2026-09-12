import { createIcon } from './icons';
import { componentIdentity, el, frameworkName } from './ui-helpers';
import type { EventMonitor } from '../core/event-monitor';
import type { PluginRegistry } from '../core/plugin-registry';
import type { ComponentDataMap } from '../types';

export class ComponentCard {
    #pulses = new Map<HTMLElement, number>();
    #registry: PluginRegistry;
    #eventMonitor: EventMonitor | null;

    constructor(registry: PluginRegistry, eventMonitor: EventMonitor | null) {
        this.#registry = registry;
        this.#eventMonitor = eventMonitor;
    }

    render(
        element: Element,
        dataMap: ComponentDataMap,
        identity = componentIdentity(element, dataMap, this.#registry)
    ): HTMLElement {
        const framework = dataMap.keys().next().value || 'default';
        const tag = element.tagName.toLowerCase();
        const name = identity.name ?? tag;
        const generatedLiveId = framework === 'livecomponent' && /^live-\d+(?:-\d+)?$/.test(element.id);
        const selector = identity.name === undefined || generatedLiveId ? tag : identity.selector;
        const entries = this.#eventMonitor?.project(element) ?? [];
        const latest = entries.at(-1);
        const card = el('article', { class: 'component', dataset: { framework } });

        const row = el(
            'button',
            {
                class: 'component-row',
                type: 'button',
                dataset: { componentLabel: `${name}, ${frameworkName(framework)} component` },
            },
            el(
                'span',
                { class: 'identity' },
                el('strong', { text: name }),
                el('small', { class: 'selector', text: selector })
            ),
            createIcon('forward')
        );

        card.appendChild(row);
        this.updateActivity(card, entries.length, latest?.label || latest?.event || '');
        return card;
    }

    updateActivity(row: Element | null | undefined, count: number, lastLabel = ''): boolean {
        const action = (row?.matches?.('.component-row') ? row : row?.querySelector?.('.component-row')) as
            | HTMLElement
            | null
            | undefined;
        if (!action) return false;
        let activity = action.querySelector('.activity') as HTMLElement | null;
        if (!count) {
            cancelAnimationFrame(this.#pulses.get(action) ?? 0);
            this.#pulses.delete(action);
            action.classList.remove('activity-pulse');
            activity?.remove();
            action.setAttribute('aria-label', action.dataset.componentLabel ?? '');
            return true;
        }
        if (!activity) {
            activity = el('span', { class: 'activity' });
            action.insertBefore(activity, action.lastElementChild);
        }
        const label = `${count} captured event${count === 1 ? '' : 's'}`;
        const title = lastLabel ? `${label} · Latest: ${lastLabel}` : label;
        if (activity.textContent === String(count) && activity.title === title) return true;
        const increased = count > Number(activity.textContent);
        activity.textContent = String(count);
        activity.setAttribute('aria-label', label);
        activity.title = title;
        action.setAttribute('aria-label', `${action.dataset.componentLabel}, ${label}`);
        if (increased && action.isConnected) {
            action.classList.remove('activity-pulse');
            cancelAnimationFrame(this.#pulses.get(action) ?? 0);
            this.#pulses.set(
                action,
                requestAnimationFrame(() => {
                    this.#pulses.delete(action);
                    if (action.isConnected) action.classList.add('activity-pulse');
                })
            );
        }
        return true;
    }

    destroy(): void {
        for (const frame of this.#pulses.values()) cancelAnimationFrame(frame);
        this.#pulses.clear();
    }
}
