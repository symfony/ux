export default class {
    promise: Promise<Response>;
    actions: string[];
    updatedModels: string[];
    isResolved: boolean;
    constructor(promise: Promise<Response>, actions: string[], updateModels: string[]);
    containsOneOfActions(targetedActions: string[]): boolean;
    areAnyModelsUpdated(targetedModels: string[]): boolean;
}
