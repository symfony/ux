import Component from './Component';
import ExternalMutationTracker from './Rendering/ExternalMutationTracker';
export declare function executeMorphdom(rootFromElement: HTMLElement, rootToElement: HTMLElement, modifiedFieldElements: Array<HTMLElement>, getElementValue: (element: HTMLElement) => any, childComponents: Component[], findChildComponent: (id: string, element: HTMLElement) => HTMLElement | null, getKeyFromElement: (element: HTMLElement) => string | null, externalMutationTracker: ExternalMutationTracker): void;
