import { queryElements, type QueryElements } from '../core/dom-query';
import { sameSnapshot } from '../core/snapshot';
import { safeUrl } from '../core/snapshot';
import type {
    ActivityDraft,
    ComponentData,
    InspectorPlugin,
    MonitoredEvents,
    PageRule,
    RelationshipEdge,
} from '../types';

interface FrameRef {
    id: string;
    element: Element;
}

interface LinkRef {
    href: string;
    element: Element;
}

interface FormRef {
    action: string;
    method: string;
    element: Element;
}

export interface TurboData {
    id: string;
    src: string;
    loading: string;
    disabled: boolean;
    target: string;
    autoscroll: boolean;
    busy: boolean;
    complete: boolean;
    childFrames: FrameRef[];
    parentFrame: FrameRef | null;
    linksToFrame: LinkRef[];
    formsInFrame: FormRef[];
    [key: string]: unknown;
}

export class TurboPlugin implements InspectorPlugin<TurboData> {
    name = 'turbo';
    selectors = ['turbo-frame'];

    canHandle(element: Element): boolean {
        return element.tagName === 'TURBO-FRAME';
    }

    parse(element: Element, query = queryElements): ComponentData<TurboData> {
        return {
            type: 'turbo',
            element,
            data: {
                id: element.id || '(anonymous)',
                src: safeUrl(element.getAttribute('src')),
                loading: element.getAttribute('loading') || 'eager',
                disabled: element.hasAttribute('disabled'),
                target: element.getAttribute('target') || '',
                autoscroll: element.hasAttribute('autoscroll'),
                busy: element.hasAttribute('busy'),
                complete: element.hasAttribute('complete'),
                childFrames: this.#findChildFrames(element),
                parentFrame: this.#findParentFrame(element),
                linksToFrame: this.#findLinksToFrame(element, query),
                formsInFrame: this.#findFormsInFrame(element, query),
            },
        };
    }

    getDisplayName(element: Element): string {
        const id = element.id || 'anonymous';
        return `Frame: ${id}`;
    }

    getRelationships(element: Element, data: ComponentData<TurboData>): RelationshipEdge[] {
        const edges: RelationshipEdge[] = [];
        const d = data.data;

        for (const child of d.childFrames) {
            if (child.element?.isConnected) {
                edges.push({
                    source: element,
                    target: child.element,
                    type: 'frame-nesting',
                    label: `contains frame: ${child.id}`,
                });
            }
        }

        if (d.parentFrame?.element?.isConnected) {
            edges.push({
                source: d.parentFrame.element,
                target: element,
                type: 'frame-nesting',
                label: `parent frame: ${d.parentFrame.id}`,
            });
        }

        return edges;
    }

    onEvent(entry: ActivityDraft, element: Element): void {
        if ('turbo:before-stream-render' === entry.event && element?.tagName === 'TURBO-STREAM') {
            const action = (element.getAttribute('action') || 'unknown').slice(0, 80);
            const target = (element.getAttribute('target') || '').slice(0, 200);
            const targets = (element.getAttribute('targets') || '').slice(0, 500);
            const method = (element.getAttribute('method') || '').slice(0, 80);
            entry.label = `stream: ${action}${target ? ` → #${target}` : targets ? ` → ${targets}` : ''}`;
            entry.detail = { action, target, targets, method };
            const impacted: Element[] = [];
            if (target) {
                const found = document.getElementById(target);
                if (found) impacted.push(found);
            }
            if (targets) {
                try {
                    impacted.push(...Array.from(document.querySelectorAll(targets)));
                } catch {
                    /* invalid selector */
                }
            }
            entry.relatedElements = [...new Set(impacted)].slice(0, 50);
            if (entry.relatedElements[0]) entry.target = entry.relatedElements[0];
            return;
        }
        if (element?.tagName === 'TURBO-FRAME') {
            entry.label = `${entry.event.slice(6)}: #${element.id || 'anonymous'}`;
        }
    }

    getMonitoredEvents(): MonitoredEvents {
        return {
            static: [
                'turbo:load',
                'turbo:visit',
                'turbo:render',
                'turbo:before-visit',
                'turbo:before-render',
                'turbo:click',
                'turbo:before-cache',
                'turbo:frame-load',
                'turbo:frame-render',
                'turbo:before-frame-render',
                'turbo:frame-missing',
                'turbo:before-frame-morph',
                'turbo:morph',
                'turbo:submit-start',
                'turbo:submit-end',
                'turbo:before-fetch-request',
                'turbo:before-fetch-response',
                'turbo:fetch-request-error',
                'turbo:before-stream-render',
            ],
        };
    }

    matchesAttribute(name: string): boolean {
        return [
            'src',
            'loading',
            'disabled',
            'busy',
            'complete',
            'autoscroll',
            'target',
            'data-turbo',
            'data-turbo-frame',
            'data-turbo-permanent',
        ].includes(name);
    }

    getPageRules(doc: Document = document): PageRule[] {
        const rules: PageRule[] = [];
        const add = (selector: string, kind: string, label: string, detail: (element: Element) => string) => {
            for (const element of doc.querySelectorAll(selector)) {
                if (element.closest('ux-inspector')) continue;
                rules.push({ kind, label, detail: detail(element), element });
            }
        };
        add(
            '[data-turbo-permanent]',
            'permanent',
            'Permanent element',
            (element: Element) => `#${element.id || '(missing id)'}`
        );
        add(
            '[data-turbo="false"]',
            'disabled',
            'Turbo disabled',
            (element: Element) => `Direct scope on ${element.tagName.toLowerCase()}`
        );
        for (const element of doc.querySelectorAll('[data-turbo-frame]')) {
            if (element.closest('ux-inspector')) continue;
            const target = element.getAttribute('data-turbo-frame');
            if (element.closest('turbo-frame')?.id === target) continue;
            const detail =
                target === '_top'
                    ? '_top - page navigation'
                    : doc.getElementById(target ?? '')?.tagName === 'TURBO-FRAME'
                      ? `#${target} - resolved`
                      : `#${target} - unresolved`;
            rules.push({ kind: 'frame-target', label: 'Frame target', detail, element });
        }
        add(
            'turbo-stream-source',
            'stream-source',
            'Stream source',
            (element: Element) => element.getAttribute('src') || element.getAttribute('channel') || 'connected'
        );
        return rules;
    }

    hasExternalChanges(element: Element, { data }: ComponentData<TurboData>, query: QueryElements): boolean {
        return (
            !sameSnapshot(data.linksToFrame, this.#findLinksToFrame(element, query)) ||
            !sameSnapshot(data.formsInFrame, this.#findFormsInFrame(element, query))
        );
    }

    #findChildFrames(element: Element): FrameRef[] {
        return Array.from(element.querySelectorAll(':scope > turbo-frame')).map((f) => ({
            id: f.id || '(anonymous)',
            element: f,
        }));
    }

    #findParentFrame(element: Element): FrameRef | null {
        const parent = element.parentElement?.closest('turbo-frame');
        if (!parent) return null;
        return {
            id: parent.id || '(anonymous)',
            element: parent,
        };
    }

    #findLinksToFrame(element: Element, query: QueryElements): LinkRef[] {
        return query('a[href]')
            .filter(
                (link) =>
                    link.closest('turbo-frame') === element || link.getAttribute('data-turbo-frame') === element.id
            )
            .map((link) => ({ href: safeUrl(link.getAttribute('href')), element: link }));
    }

    #findFormsInFrame(element: Element, query: QueryElements): FormRef[] {
        return query('form')
            .filter(
                (form) =>
                    form.closest('turbo-frame') === element || form.getAttribute('data-turbo-frame') === element.id
            )
            .map((form) => ({
                action: safeUrl(form.getAttribute('action')),
                method: form.getAttribute('method') || 'get',
                element: form,
            }));
    }
}
