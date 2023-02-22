import { getDeepData, setDeepData } from '../data_manipulation_utils';
import { normalizeModelName } from '../string_utils';

export default class {
    private readonly identifierKey = '@id';

    updatedModels: string[] = [];
    private props: any = {};

    constructor(props: any) {
        this.props = props;
    }

    /**
     * Returns the props with the given name.
     *
     * This allows for non-normalized model names - e.g.
     * user[firstName] -> user.firstName and also will fetch
     * deeply (fetching the "firstName" sub-key from the "user" key).
     */
    get(name: string): any {
        const normalizedName = normalizeModelName(name);

        const value = getDeepData(this.props, normalizedName);

        if (null === value) {
            return value;
        }

        // if normalizedName is "top level" and value is an object,
        // and the value has an "@id" key, then return the "@id" key.
        if (this.isPropNameTopLevel(normalizedName) && typeof value === 'object' && value[this.identifierKey] !== undefined) {
            return value[this.identifierKey];
        }

        return value;
    }

    has(name: string): boolean {
        return this.get(name) !== undefined;
    }

    /**
     * Sets data back onto the value store.
     *
     * The name can be in the non-normalized format.
     *
     * Returns true if the new value is different from the existing value.
     */
    set(name: string, value: any): boolean {
        let normalizedName = normalizeModelName(name);

        // if normalizedName is a "top level" key and currentValue is an object
        // then change normalizedName to set the "@id" key.
        if (this.isPropNameTopLevel(normalizedName)
            && this.props[normalizedName] !== null
            && typeof this.props[normalizedName] === 'object'
            && this.props[normalizedName][this.identifierKey] !== undefined
        ) {
            normalizedName = normalizedName + '.' + this.identifierKey;
        }

        const currentValue = this.get(normalizedName);

        if (currentValue !== value && !this.updatedModels.includes(normalizedName)) {
            this.updatedModels.push(normalizedName);
        }

        this.props = setDeepData(this.props, normalizedName, value);

        return currentValue !== value;
    }

    all(): any {
        return { ...this.props };
    }

    /**
     * Set the props to a fresh set from the server.
     *
     * @param props
     */
    reinitializeAllProps(props: any): void {
        this.updatedModels = [];
        this.props = props;
    }

    /**
     * Reinitialize *only* the provided props, but leave other untouched.
     *
     * This is used when a parent component is rendering, and it includes
     * a fresh set of the "readonly" props for a child. This allows any writable
     * props to remain (as these may have already been changed by the user).
     *
     * The server manages returning only the readonly props, so we don't need to
     * worry about that.
     *
     * If a prop is readonly, it will also include all of its "writable" paths
     * data. So, that embedded, writable data *is* overwritten. For example,
     * if the "user" data is currently { '@id': 123, firstName: 'Ryan' } and
     * the "user" prop changes to "456", the new "user" prop passed here will
     * be { '@id': 456, firstName: 'Kevin' }. This will overwrite the "firstName",
     * writable embedded data.
     *
     * Returns true if any of the props changed.
     */
    reinitializeProvidedProps(props: any): boolean {
        let changed = false;

        for (const [key, value] of Object.entries(props)) {
            const currentIdentifier = this.get(key);
            const newIdentifier = this.findIdentifier(value);

            // if the readonly identifier is different, then overwrite
            // the prop entirely, including embedded writable data.
            if (currentIdentifier !== newIdentifier) {
                changed = true;
                this.props[key] = value;
            }
        }

        return changed;
    }
    private isPropNameTopLevel(key: string): boolean {
        return key.indexOf('.') === -1;
    }

    private findIdentifier(value: any): any {
        if (typeof value !== 'object' || value[this.identifierKey] === undefined) {
            return value;
        }

        return value[this.identifierKey];
    }
}
