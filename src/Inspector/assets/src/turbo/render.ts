import { makeField, makeElementField, makeGroup, makeKeyValueList } from '../ui/fields';
import type { ComponentData, RenderContext } from '../types';
import type { TurboData } from './plugin';
export function renderTurbo(data: ComponentData<TurboData>, context: RenderContext = {}): DocumentFragment {
    const frag = document.createDocumentFragment();
    const d = data.data;
    const state = [makeField('loading', d.loading)];
    const actions: HTMLElement[] = [];
    if (d.src) state.push(makeField('src', d.src));
    if (d.target) state.push(makeField('target', d.target));
    for (const key of ['disabled', 'autoscroll', 'busy', 'complete']) {
        if (d[key]) state.push(makeField(key, true));
    }

    const links = d.linksToFrame.map((link, index: number) =>
        makeElementField(`link${d.linksToFrame.length > 1 ? `[${index + 1}]` : ''}`, link.element, {
            framework: 'turbo',
            badge: 'Frame link',
            detail: link.href,
        })
    );
    const forms = d.formsInFrame.map((form, index: number) =>
        makeElementField(`form${d.formsInFrame.length > 1 ? `[${index + 1}]` : ''}`, form.element, {
            framework: 'turbo',
            badge: 'Frame form',
            detail: `${form.method.toUpperCase()} ${form.action || '(current URL)'}`,
        })
    );
    for (const entry of context.events || []) {
        if (entry.event !== 'turbo:before-stream-render') continue;
        const detail = (entry.detail ?? {}) as { action?: string; target?: string; targets?: string };
        const action = detail.action || 'stream';
        const target = detail.target ? `#${detail.target}` : detail.targets || 'document';
        actions.push(makeField(action, target));
    }
    const groups = [
        makeGroup('Frame', [makeKeyValueList(state)], { key: 'turbo-frame', icon: 'components' }),
        makeGroup('Actions', [makeKeyValueList(actions)], { key: 'turbo-actions', icon: 'activity' }),
        makeGroup('Links', [makeKeyValueList(links)], { key: 'turbo-links', icon: 'overlay' }),
        makeGroup('Forms', [makeKeyValueList(forms)], { key: 'turbo-forms', icon: 'overlay' }),
    ].filter(Boolean);
    if (groups.length) {
        const container = document.createElement('div');
        container.className = 'groups';
        container.append(...(groups as HTMLElement[]));
        frag.append(container);
    }
    return frag;
}
