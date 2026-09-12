import { renderTurbo } from '../../../src/turbo/render';
import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { TurboPlugin } from '../../../src/turbo/plugin';

describe('TurboPlugin', () => {
    let plugin;

    beforeEach(() => {
        plugin = new TurboPlugin();
        document.body.innerHTML = '';
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    function createFrame(attrs = {}) {
        const el = document.createElement('turbo-frame');
        for (const [key, value] of Object.entries(attrs)) {
            if (value === true) {
                el.setAttribute(key, '');
            } else if (value !== false && value !== null) {
                el.setAttribute(key, value);
            }
        }
        return el;
    }

    describe('canHandle()', () => {
        it('returns true for turbo-frame elements', () => {
            const el = createFrame({ id: 'main' });
            expect(plugin.canHandle(el)).toBe(true);
        });

        it('returns false for other elements', () => {
            const el = document.createElement('div');
            expect(plugin.canHandle(el)).toBe(false);
        });
    });

    describe('parse()', () => {
        it('extracts frame ID', () => {
            const el = createFrame({ id: 'content' });
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.id).toBe('content');
        });

        it('extracts src attribute', () => {
            const el = createFrame({ id: 'f', src: '/page' });
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.src).toBe('/page');
        });

        it('extracts loading mode (eager/lazy)', () => {
            const el = createFrame({ id: 'f', loading: 'lazy' });
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.loading).toBe('lazy');
        });

        it('defaults loading to eager', () => {
            const el = createFrame({ id: 'f' });
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.loading).toBe('eager');
        });

        it('detects disabled state', () => {
            const el = createFrame({ id: 'f', disabled: true });
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.disabled).toBe(true);
        });

        it('detects busy state', () => {
            const el = createFrame({ id: 'f', busy: true });
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.busy).toBe(true);
        });

        it('finds child frames (direct children only)', () => {
            const parent = createFrame({ id: 'parent' });
            const child1 = createFrame({ id: 'child1' });
            const child2 = createFrame({ id: 'child2' });
            parent.appendChild(child1);
            parent.appendChild(child2);
            document.body.appendChild(parent);

            const result = plugin.parse(parent);
            expect(result.data.childFrames).toHaveLength(2);
            expect(result.data.childFrames[0].id).toBe('child1');
            expect(result.data.childFrames[0].element).toBe(child1);
            expect(result.data.childFrames[1].id).toBe('child2');
            expect(result.data.childFrames[1].element).toBe(child2);
        });

        it('uses "(anonymous)" for frames without id', () => {
            const el = createFrame({});
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.id).toBe('(anonymous)');
        });

        it('finds parent frame', () => {
            const parent = createFrame({ id: 'outer' });
            const child = createFrame({ id: 'inner' });
            parent.appendChild(child);
            document.body.appendChild(parent);

            const result = plugin.parse(child);
            expect(result.data.parentFrame).not.toBeNull();
            expect(result.data.parentFrame.id).toBe('outer');
            expect(result.data.parentFrame.element).toBe(parent);
        });

        it('returns null parentFrame when no parent frame exists', () => {
            const el = createFrame({ id: 'root' });
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.parentFrame).toBeNull();
        });

        it('finds links inside the frame', () => {
            const frame = createFrame({ id: 'nav' });
            const link = document.createElement('a');
            link.setAttribute('href', '/page');
            frame.appendChild(link);
            document.body.appendChild(frame);

            const result = plugin.parse(frame);
            expect(result.data.linksToFrame).toHaveLength(1);
            expect(result.data.linksToFrame[0].href).toBe('/page');
            expect(result.data.linksToFrame[0].element).toBe(link);
        });

        it('excludes links inside nested frames', () => {
            const outer = createFrame({ id: 'outer' });
            const inner = createFrame({ id: 'inner' });
            const link = document.createElement('a');
            link.setAttribute('href', '/nested');
            inner.appendChild(link);
            outer.appendChild(inner);
            document.body.appendChild(outer);

            const result = plugin.parse(outer);
            expect(result.data.linksToFrame).toHaveLength(0);
        });

        it('finds forms inside the frame', () => {
            const frame = createFrame({ id: 'form-frame' });
            const form = document.createElement('form');
            form.setAttribute('action', '/submit');
            form.setAttribute('method', 'post');
            frame.appendChild(form);
            document.body.appendChild(frame);

            const result = plugin.parse(frame);
            expect(result.data.formsInFrame).toHaveLength(1);
            expect(result.data.formsInFrame[0].action).toBe('/submit');
            expect(result.data.formsInFrame[0].method).toBe('post');
            expect(result.data.formsInFrame[0].element).toBe(form);
        });
        it('detects complete state', () => {
            const el = createFrame({ id: 'f', complete: true });
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.complete).toBe(true);
        });

        it('detects autoscroll attribute', () => {
            const el = createFrame({ id: 'f', autoscroll: true });
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.autoscroll).toBe(true);
        });

        it('extracts target attribute', () => {
            const el = createFrame({ id: 'f', target: '_top' });
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.target).toBe('_top');
        });

        it('excludes forms inside nested frames', () => {
            const outer = createFrame({ id: 'outer' });
            const inner = createFrame({ id: 'inner' });
            const form = document.createElement('form');
            form.setAttribute('action', '/nested-submit');
            form.setAttribute('method', 'post');
            inner.appendChild(form);
            outer.appendChild(inner);
            document.body.appendChild(outer);

            const result = plugin.parse(outer);
            expect(result.data.formsInFrame).toHaveLength(0);
        });

        it('finds links that target the frame via data-turbo-frame', () => {
            const frame = createFrame({ id: 'content' });
            const link = document.createElement('a');
            link.setAttribute('href', '/page2');
            link.setAttribute('data-turbo-frame', 'content');
            document.body.append(frame, link);

            const result = plugin.parse(frame);
            expect(result.data.linksToFrame).toHaveLength(1);
            expect(result.data.linksToFrame[0].href).toBe('/page2');
            expect(result.data.linksToFrame[0].element).toBe(link);
        });

        it('finds multiple forms inside the frame', () => {
            const frame = createFrame({ id: 'form-frame' });
            const form1 = document.createElement('form');
            form1.setAttribute('action', '/submit1');
            form1.setAttribute('method', 'post');
            const form2 = document.createElement('form');
            form2.setAttribute('action', '/submit2');
            form2.setAttribute('method', 'get');
            frame.appendChild(form1);
            frame.appendChild(form2);
            document.body.appendChild(frame);

            const result = plugin.parse(frame);
            expect(result.data.formsInFrame).toHaveLength(2);
            expect(result.data.formsInFrame[0].action).toBe('/submit1');
            expect(result.data.formsInFrame[1].action).toBe('/submit2');
        });
    });

    describe('canHandle()', () => {
        it('returns false for non-turbo-frame elements', () => {
            const el = document.createElement('div');
            expect(plugin.canHandle(el)).toBe(false);
        });

        it('returns false for turbo-stream elements', () => {
            const el = document.createElement('turbo-stream');
            expect(plugin.canHandle(el)).toBe(false);
        });
    });

    describe('getDisplayName()', () => {
        it('returns "Frame: {id}"', () => {
            const el = createFrame({ id: 'main' });
            expect(plugin.getDisplayName(el)).toBe('Frame: main');
        });

        it('returns "Frame: anonymous" for frames without id', () => {
            const el = createFrame({});
            expect(plugin.getDisplayName(el)).toBe('Frame: anonymous');
        });

        it('returns frame ID in display name', () => {
            const el = createFrame({ id: 'sidebar' });
            expect(plugin.getDisplayName(el)).toBe('Frame: sidebar');
        });
    });

    describe('renderCard()', () => {
        it('renders the expected data fields as a DocumentFragment', () => {
            const data = {
                data: {
                    id: 'test-frame',
                    src: '/src',
                    loading: 'eager',
                    childFrames: [],
                    parentFrame: null,
                    linksToFrame: [],
                    formsInFrame: [],
                },
            };
            const frag = renderTurbo(data);
            expect(frag).toBeTruthy();
        });

        it('groups frame metadata and captured Stream actions', () => {
            const data = {
                data: {
                    id: 'messages',
                    src: '/messages',
                    loading: 'lazy',
                    target: '',
                    disabled: false,
                    autoscroll: false,
                    busy: false,
                    complete: true,
                    childFrames: [],
                    parentFrame: null,
                    linksToFrame: [],
                    formsInFrame: [],
                },
            };
            const container = document.createElement('div');
            container.append(
                renderTurbo(data, {
                    events: [
                        {
                            event: 'turbo:before-stream-render',
                            detail: { action: 'append', target: 'messages', targets: '' },
                        },
                    ],
                })
            );

            expect(
                [...container.querySelectorAll('.groups > .group > .title > .name')].map((title) => title.textContent)
            ).toEqual(['Frame', 'Actions']);
            expect(container.querySelector('[data-group="turbo-frame"]').textContent).toContain('loadinglazy');
            expect(container.querySelector('[data-group="turbo-actions"]').textContent).toContain('append#messages');
            expect(container.querySelector('[data-group="turbo-actions"] > .title > .badge')).toBeNull();
            expect(container.querySelector('[data-tab]')).toBeNull();
        });

        it('shows links and forms targeting the frame as qualified relations', () => {
            const link = document.createElement('a');
            link.href = '/messages';
            const form = document.createElement('form');
            form.action = '/messages';
            const container = document.createElement('div');
            container.append(
                renderTurbo({
                    data: {
                        id: 'parent',
                        src: '',
                        loading: 'eager',
                        target: '',
                        disabled: false,
                        autoscroll: false,
                        busy: false,
                        complete: true,
                        childFrames: [],
                        parentFrame: null,
                        linksToFrame: [{ href: '/messages', element: link }],
                        formsInFrame: [{ action: '/messages', method: 'post', element: form }],
                    },
                })
            );

            expect(container.querySelector('[data-group="turbo-links"] > .title > .name').textContent).toBe('Links');
            expect(container.querySelector('[data-group="turbo-forms"] > .title > .name').textContent).toBe('Forms');
            expect(container.querySelectorAll('.group > .title > .badge')).toHaveLength(0);
        });
    });

    describe('events', () => {
        it('enriches Turbo Stream actions without retaining stream contents', () => {
            const stream = document.createElement('turbo-stream');
            stream.setAttribute('action', 'append');
            stream.setAttribute('target', 'messages');
            stream.innerHTML = '<template><p>private content</p></template>';
            const entry = { event: 'turbo:before-stream-render', detail: { newStream: 'raw' } };

            plugin.onEvent(entry, stream);

            expect(entry.label).toBe('stream: append → #messages');
            expect(entry.detail).toEqual({ action: 'append', target: 'messages', targets: '', method: '' });
            expect(JSON.stringify(entry)).not.toContain('private content');
        });

        it('monitors frame failures, morphs, fetches and stream rendering', () => {
            const events = plugin.getMonitoredEvents().static;
            expect(events).toEqual(
                expect.arrayContaining([
                    'turbo:frame-missing',
                    'turbo:before-frame-morph',
                    'turbo:morph',
                    'turbo:before-fetch-request',
                    'turbo:before-fetch-response',
                    'turbo:fetch-request-error',
                    'turbo:before-stream-render',
                ])
            );
        });

        it('captures actual Stream targets before the stream disappears', () => {
            const target = document.createElement('div');
            target.id = 'messages';
            document.body.appendChild(target);
            const stream = document.createElement('turbo-stream');
            stream.setAttribute('action', 'replace');
            stream.setAttribute('target', 'messages');
            const entry = { event: 'turbo:before-stream-render', target: stream };

            plugin.onEvent(entry, stream);

            expect(entry.target).toBe(target);
            expect(entry.relatedElements).toEqual([target]);
        });

        it('exposes page rules without treating them as frame components', () => {
            document.body.innerHTML = `
                <div id="nav" data-turbo-permanent></div>
                <section data-turbo="false"></section>
                <a data-turbo-frame="results"></a>
                <turbo-stream-source src="/updates"></turbo-stream-source>
                <turbo-frame id="results"></turbo-frame>
            `;

            const rules = plugin.getPageRules();

            expect(rules.map((rule) => rule.kind)).toEqual(['permanent', 'disabled', 'frame-target', 'stream-source']);
            expect(rules.find((rule) => rule.kind === 'frame-target').detail).toContain('resolved');
        });
    });
});
