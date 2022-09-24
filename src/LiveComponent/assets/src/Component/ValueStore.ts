import { getDeepData, setDeepData } from '../data_manipulation_utils';
import { normalizeModelName } from '../string_utils';

export default class {
    updatedModels: string[] = [];
    private data: any = {};

    constructor(data: any) {
        this.data = data;
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

        return getDeepData(this.data, normalizedName);
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

        this.data = setDeepData(this.data, normalizedName, value);
    }

    /**
     * Checks if the given name/propertyPath is for a valid top-level key.
     */
    hasAtTopLevel(name: string): boolean {
        const parts = name.split('.');

        return this.data[parts[0]] !== undefined;
    }

    all(): any {
        return this.data;
    }

    reinitialize(data: any) {
        this.data = data;
    }
}
