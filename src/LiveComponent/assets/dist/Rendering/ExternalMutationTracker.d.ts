import ElementChanges from './ElementChanges';
export default class {
    private element;
    private shouldTrackChangeCallback;
    private mutationObserver;
    private changedElements;
    changedElementsCount: number;
    private addedElements;
    private removedElements;
    private isStarted;
    constructor(element: Element, shouldTrackChangeCallback: (element: Element) => boolean);
    start(): void;
    stop(): void;
    getChangedElement(element: Element): ElementChanges | null;
    getAddedElements(): Element[];
    wasElementAdded(element: Element): boolean;
    handlePendingChanges(): void;
    private onMutations;
    private handleChildListMutation;
    private handleAttributeMutation;
    private handleClassAttributeMutation;
    private handleStyleAttributeMutation;
    private handleGenericAttributeMutation;
    private extractStyles;
    private isElementAddedByTranslation;
}
