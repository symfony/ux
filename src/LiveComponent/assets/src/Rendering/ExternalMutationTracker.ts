import ElementChanges from './ElementChanges';

/**
 * Uses MutationObserver to track changes to the DOM inside a component.
 *
 * This is meant to track changes that are made by external code - i.e. not
 * a change from a component re-render.
 */
export default class {
    private element: Element;
    private shouldTrackChangeCallback: (element: Element) => boolean;
    private mutationObserver: MutationObserver;
    private changedElements: WeakMap<Element, ElementChanges> = new WeakMap();
    /** For testing */
    public changedElementsCount = 0;
    private addedElements: Array<Element> = [];
    private removedElements: Array<Element> = [];
    private isStarted = false;

    constructor(element: Element, shouldTrackChangeCallback: (element: Element) => boolean) {
        this.element = element;
        this.shouldTrackChangeCallback = shouldTrackChangeCallback;
        this.mutationObserver = new MutationObserver(this.onMutations.bind(this));
    }

    start(): void {
        if (this.isStarted) {
            return;
        }

        this.mutationObserver.observe(this.element, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeOldValue: true,
        });
        this.isStarted = true;
    }

    stop(): void {
        if (this.isStarted) {
            this.mutationObserver.disconnect();

            this.isStarted = false;
        }
    }

    getChangedElement(element: Element): ElementChanges | null {
        return this.changedElements.has(element) ? (this.changedElements.get(element) as ElementChanges) : null;
    }

    getAddedElements(): Element[] {
        return this.addedElements;
    }

    wasElementAdded(element: Element): boolean {
        return this.addedElements.includes(element);
    }

    /**
     * Forces any pending mutations to be handled immediately, then clears the queue.
     */
    handlePendingChanges(): void {
        this.onMutations(this.mutationObserver.takeRecords());
    }

    private onMutations(mutations: MutationRecord[]): void {
        // keyed by the Element the mutation occurred on, with an array of attribute string names
        const handledAttributeMutations: WeakMap<Element, string[]> = new WeakMap();
        for (const mutation of mutations) {
            const element = mutation.target as Element;

            // ignore mutations that are not inside this component - e.g. inside a child
            if (!this.shouldTrackChangeCallback(element)) {
                continue;
            }

            if (this.isElementAddedByTranslation(element)) {
                continue;
            }

            // ignore changes in elements that were externally-added
            let isChangeInAddedElement = false;
            for (const addedElement of this.addedElements) {
                if (addedElement.contains(element)) {
                    isChangeInAddedElement = true;
                    break;
                }
            }

            if (isChangeInAddedElement) {
                continue;
            }

            switch (mutation.type) {
                case 'childList':
                    this.handleChildListMutation(mutation);
                    break;
                case 'attributes':
                    if (!handledAttributeMutations.has(element)) {
                        handledAttributeMutations.set(element, []);
                    }

                    // only process the first attribute mutation: it will have the
                    // true original value, and we'll look at the true new value
                    if (
                        !(handledAttributeMutations.get(element) as string[]).includes(mutation.attributeName as string)
                    ) {
                        this.handleAttributeMutation(mutation);

                        // add this attribute to the list of handled attributes
                        handledAttributeMutations.set(element, [
                            ...(handledAttributeMutations.get(element) as string[]),
                            mutation.attributeName as string,
                        ]);
                    }
                    break;
            }
        }
    }

    private handleChildListMutation(mutation: MutationRecord): void {
        // note: we currently ignore removed nodes: if a node is removed
        // by external code, it will be added back on re-render. Hide
        // elements instead of removing them.
        // TODO: at least handle removing previously-added nodes

        mutation.addedNodes.forEach((node) => {
            if (!(node instanceof Element)) {
                return;
            }

            if (this.removedElements.includes(node)) {
                // this node was removed then added back, so no net change
                this.removedElements.splice(this.removedElements.indexOf(node), 1);

                return;
            }

            if (this.isElementAddedByTranslation(node)) {
                return;
            }

            this.addedElements.push(node);
        });

        mutation.removedNodes.forEach((node) => {
            if (!(node instanceof Element)) {
                return;
            }

            if (this.addedElements.includes(node)) {
                // this node was added then removed, so no net change
                this.addedElements.splice(this.addedElements.indexOf(node), 1);

                return;
            }

            this.removedElements.push(node);
        });
    }

    private handleAttributeMutation(mutation: MutationRecord): void {
        const element = mutation.target as Element;

        if (!this.changedElements.has(element)) {
            this.changedElements.set(element, new ElementChanges());
            this.changedElementsCount++;
        }
        const changedElement = this.changedElements.get(element) as ElementChanges;

        switch (mutation.attributeName) {
            case 'class':
                this.handleClassAttributeMutation(mutation, changedElement);
                break;
            case 'style':
                this.handleStyleAttributeMutation(mutation, changedElement);
                break;
            default:
                this.handleGenericAttributeMutation(mutation, changedElement);
        }

        if (changedElement.isEmpty()) {
            this.changedElements.delete(element);
            this.changedElementsCount--;
        }
    }

    private handleClassAttributeMutation(mutation: MutationRecord, elementChanges: ElementChanges) {
        const element = mutation.target as Element;

        const previousValue = mutation.oldValue || '';
        const previousValues: string[] = previousValue.match(/(\S+)/gu) || [];

        const newValues: string[] = [].slice.call(element.classList);
        const addedValues = newValues.filter((value) => !previousValues.includes(value));
        const removedValues = previousValues.filter((value) => !newValues.includes(value));

        addedValues.forEach((value) => {
            elementChanges.addClass(value);
        });
        removedValues.forEach((value) => {
            elementChanges.removeClass(value);
        });
    }

    private handleStyleAttributeMutation(mutation: MutationRecord, elementChanges: ElementChanges) {
        const element = mutation.target as Element;

        const previousValue = mutation.oldValue || '';
        const previousStyles = this.extractStyles(previousValue);

        const newValue = element.getAttribute('style') || '';
        const newStyles = this.extractStyles(newValue);

        const addedOrChangedStyles = Object.keys(newStyles).filter(
            (key) => previousStyles[key] === undefined || previousStyles[key] !== newStyles[key]
        );
        const removedStyles = Object.keys(previousStyles).filter((key) => !newStyles[key]);

        addedOrChangedStyles.forEach((style) => {
            elementChanges.addStyle(
                style,
                newStyles[style],
                previousStyles[style] === undefined ? null : previousStyles[style]
            );
        });

        removedStyles.forEach((style) => {
            elementChanges.removeStyle(style, previousStyles[style]);
        });
    }

    private handleGenericAttributeMutation(mutation: MutationRecord, elementChanges: ElementChanges) {
        const attributeName = mutation.attributeName as string;
        const element = mutation.target as Element;

        let oldValue = mutation.oldValue;
        let newValue = element.getAttribute(attributeName) as string;
        // try to normalize situations like: disabled="disabled"
        // we don't want the value "disabled" and *no* value to be seen as a change
        if (oldValue === attributeName) {
            oldValue = '';
        }
        if (newValue === attributeName) {
            newValue = '';
        }

        if (!element.hasAttribute(attributeName)) {
            if (oldValue === null) {
                // null means the attribute was added, but since it's
                // not present currently, it was likely later removed
                // inside this same set of mutations. So, do nothing
                return;
            }

            elementChanges.removeAttribute(attributeName, mutation.oldValue as string);

            return;
        }

        if (newValue === oldValue) {
            // the attribute was changed, but it matches its current value
            // likely the value was changed, then changed back all within
            // the same set of mutations. So, do nothing
            return;
        }

        elementChanges.addAttribute(attributeName, element.getAttribute(attributeName) as string, mutation.oldValue);
    }

    private extractStyles(styles: string): { [key: string]: string } {
        const styleObject: { [key: string]: string } = {};
        styles.split(';').forEach((style) => {
            // split on the first ":" only
            const parts = style.split(':');
            // skip invalid styles (likely just whitespace)
            if (parts.length === 1) {
                return;
            }

            const property = parts[0].trim();
            styleObject[property] = parts.slice(1).join(':').trim();
        });

        return styleObject;
    }

    /**
     * Helps avoid tracking changes by Chrome's translation feature.
     *
     * When Chrome translates, it mutates the dom in a way that triggers MutationObserver.
     * This includes adding new elements wrapped in a <font> tag. This causes live
     * components to incorrectly think that these new elements should persist through
     * re-renders, causing duplicate text.
     */
    private isElementAddedByTranslation(element: Element): boolean {
        return element.tagName === 'FONT' && element.getAttribute('style') === 'vertical-align: inherit;';
    }
}
