import { createIcon } from './icons';
import { el } from './ui-helpers';

export interface DrillLevel {
    id: string;
    title: string;
    header: HTMLButtonElement;
    content: HTMLDivElement;
    element?: Element;
    scrollTop: number;
}

export interface DrillPushOptions {
    id: string;
    title: string;
    typePill?: string;
    content?: Node | null;
    element?: Element;
    framework?: string;
}

export class DrillStack extends EventTarget {
    #levels: DrillLevel[] = [];
    #headers: HTMLDivElement;
    #content: HTMLDivElement;
    #element: HTMLElement;

    constructor(root: Node, { id = 'root', title = 'Components' }: { id?: string; title?: string } = {}) {
        super();
        this.#headers = el('div', { class: 'stack-nav' });
        this.#content = el('div', { class: 'stack-body' });
        this.#element = el('section', { class: 'stack pane' }, this.#headers, this.#content);
        this.push({ id, title, content: root }, false);
    }

    get element(): HTMLElement {
        return this.#element;
    }
    get depth(): number {
        return this.#levels.length;
    }
    get current(): DrillLevel | null {
        return this.#levels.at(-1) ?? null;
    }

    push({ id, title, typePill, content, element, framework }: DrillPushOptions, notify = true): DrillLevel {
        const previous = this.current;
        if (previous) {
            previous.scrollTop = this.#content.scrollTop;
            previous.content.hidden = true;
        }
        const index = this.#levels.length;
        const header = el(
            'button',
            {
                class: 'stack-link current',
                type: 'button',
                'aria-current': 'page',
                'aria-label': index ? `Back from ${title}` : title,
                on: {
                    click: () => {
                        if (index === this.#levels.length - 1) this.pop();
                        else this.popTo(index);
                    },
                },
            },
            createIcon('back'),
            el('span', { class: 'stack-title', text: title }),
            typePill
                ? el('span', { class: 'stack-badge', dataset: { framework: framework ?? '' }, text: typePill })
                : null
        );
        const layer = el('div', { class: 'stack-page pane' }, content);
        const level: DrillLevel = { id, title, header, content: layer, element, scrollTop: 0 };
        this.#levels.push(level);
        this.#headers.appendChild(header);
        this.#content.appendChild(layer);
        this.#content.scrollTop = 0;
        this.#syncNavigation();
        if (notify) this.dispatchEvent(new CustomEvent('drill-push', { detail: { level } }));
        return level;
    }

    pop(): DrillLevel | null {
        if (this.depth <= 1) return null;
        const removed = this.#levels.pop() as DrillLevel;
        removed.header.remove();
        removed.content.remove();
        const current = this.current as DrillLevel;
        current.content.hidden = false;
        this.#content.scrollTop = current.scrollTop;
        this.#syncNavigation();
        this.dispatchEvent(new CustomEvent('drill-pop', { detail: { removed, current } }));
        return removed;
    }

    popTo(index: number): void {
        if (index < 0 || index >= this.depth || index === this.depth - 1) return;
        while (this.depth > index + 1) this.pop();
    }

    setRootTitle(title: string): void {
        const root = this.#levels[0];
        if (!root) return;
        root.title = title;
        const label = root.header.querySelector('.stack-title');
        if (label) label.textContent = title;
    }

    #syncNavigation(): void {
        const currentIndex = this.#levels.length - 1;
        const previousIndex = currentIndex - 1;
        for (const [index, level] of this.#levels.entries()) {
            const current = index === currentIndex;
            level.header.classList.toggle('current', current);
            level.header.classList.toggle('previous', index === previousIndex);
            level.header.toggleAttribute('aria-current', current);
            level.header.setAttribute('aria-label', current ? level.title : `Back to ${level.title}`);
        }
        this.#element.classList.toggle('is-drilled', this.depth > 1);
    }
}
