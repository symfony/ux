import { getDeepData, setDeepData } from './data_manipulation_utils';
import { LiveController } from './live_controller';
import { normalizeModelName } from './string_utils';

export default class {
    controller: LiveController;
    updatedModels: string[] = [];

    constructor(liveController: LiveController) {
        this.controller = liveController;
    }

    /**
     * Returns the data with the given name.
     *
     * This allows for non-normalized model names - e.g.
     * user[firstName] -> user.firstName and also will fetch
     * deeply (fetching the "firstName" sub-key from the "user" key).
     */
    get(name: string): any {
        const normalizedName = normalizeModelName(name);

        return getDeepData(this.controller.dataValue, normalizedName);
    }

    has(name: string): boolean {
        return this.get(name) !== undefined;
    }

    /**
     * Sets data back onto the value store.
     *
     * The name can be in the non-normalized format.
     */
    set(name: string, value: any): void {
        const normalizedName = normalizeModelName(name);
        if (!this.updatedModels.includes(normalizedName)) {
            this.updatedModels.push(normalizedName);
        }

        this.controller.dataValue = setDeepData(this.controller.dataValue, normalizedName, value);
    }

    /**
     * Checks if the given name/propertyPath is for a valid top-level key.
     */
    hasAtTopLevel(name: string): boolean {
        const parts = name.split('.');

        return this.controller.dataValue[parts[0]] !== undefined;
    }

    asJson(): string {
        return JSON.stringify(this.controller.dataValue);
    }

    all(): any {
        return this.controller.dataValue;
    }

    /**
     * Are any of the passed models currently "updated"?
     */
    areAnyModelsUpdated(targetedModels: string[]): boolean {
        return (this.updatedModels.filter(modelName => targetedModels.includes(modelName))).length > 0;
    }
}
