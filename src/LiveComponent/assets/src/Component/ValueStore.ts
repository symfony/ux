import { getDeepData, setDeepData } from '../data_manipulation_utils';
import { normalizeModelName } from '../string_utils';

export default class {
    updatedModels: string[] = [];
    private props: any = {};
    private data: any = {};

    constructor(props: any, data: any) {
        this.props = props;
        this.data = data;
    }

    /**
     * Returns the data or props with the given name.
     *
     * This allows for non-normalized model names - e.g.
     * user[firstName] -> user.firstName and also will fetch
     * deeply (fetching the "firstName" sub-key from the "user" key).
     */
    get(name: string): any {
        const normalizedName = normalizeModelName(name);

        const result = getDeepData(this.data, normalizedName);

        if (result !== undefined) {
            return result;
        }

        return getDeepData(this.props, normalizedName);
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

    all(): any {
        return { ...this.props, ...this.data };
    }

    reinitializeData(data: any) {
        this.data = data;
    }
}
