export default class {
    promise: Promise<Response>;
    actions: string[];
    updatedModels: string[];
    isResolved = false;

    constructor(promise: Promise<Response>, actions: string[], updateModels: string[]) {
        this.promise = promise;
        this.promise.then((response) => {
            this.isResolved = true;

            return response;
        });
        this.actions = actions;
        this.updatedModels = updateModels;
    }

    /**
     * Does this BackendRequest contain at least on action in targetedActions?
     */
    containsOneOfActions(targetedActions: string[]): boolean {
        return this.actions.filter((action) => targetedActions.includes(action)).length > 0;
    }

    /**
     * Does this BackendRequest includes updates for any of these models?
     */
    areAnyModelsUpdated(targetedModels: string[]): boolean {
        return this.updatedModels.filter((model) => targetedModels.includes(model)).length > 0;
    }
}
