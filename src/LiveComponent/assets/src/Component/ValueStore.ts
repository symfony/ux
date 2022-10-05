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

    /**
     * Set the data to a fresh set from the server.
     *
     * @param data
     */
    reinitializeData(data: any): void {
        this.updatedModels = [];
        this.data = data;
    }

    /**
     * Set the props to a fresh set from the server.
     *
     * Props can only change as a result of a parent component re-rendering.
     *
     * Returns true if any of the props changed.
     */
    reinitializeProps(props: any): boolean {
        if (JSON.stringify(props) == JSON.stringify(this.props)) {
            return false;
        }

        this.props = props;

        return true;
    }
}
