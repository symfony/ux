import type ExternalMutationTracker from './Rendering/ExternalMutationTracker';
export declare function executeMorphdom(rootFromElement: HTMLElement, rootToElement: HTMLElement, modifiedFieldElements: Array<HTMLElement>, getElementValue: (element: HTMLElement) => any, externalMutationTracker: ExternalMutationTracker): void;
