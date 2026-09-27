export type QueryElements = (selector: string) => readonly Element[];

export const TOOLING_SELECTOR = 'ux-inspector, .sf-toolbar';

/** A malformed configured selector is diagnostic data, not an application error. */
export const queryElements: QueryElements = (selector) => {
    try {
        return [...document.querySelectorAll(selector)];
    } catch {
        return [];
    }
};

/** Share DOM reads only within one synchronous scan or mutation transaction. */
export function createQueryCache(): QueryElements {
    const matches = new Map<string, readonly Element[]>();
    return (selector) => {
        let elements = matches.get(selector);
        if (!elements) matches.set(selector, (elements = queryElements(selector)));
        return elements;
    };
}

export function sameElements(first: readonly Element[], second: readonly Element[]): boolean {
    return first.length === second.length && first.every((element, index) => element === second[index]);
}

export function ancestors(element: Element, selector: string): Element[] {
    const matches: Element[] = [];
    for (
        let parent = element.parentElement?.closest(selector);
        parent;
        parent = parent.parentElement?.closest(selector)
    )
        matches.push(parent);
    return matches;
}

export function scopedChildren(element: Element, selector: string): Element[] {
    return [...element.querySelectorAll(selector)].filter(
        (child) => child.parentElement?.closest(selector) === element
    );
}
