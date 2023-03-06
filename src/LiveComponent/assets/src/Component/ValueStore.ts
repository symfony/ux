import { getDeepData } from '../data_manipulation_utils';
import { normalizeModelName } from '../string_utils';

export default class {
    /**
     * Original, read-only props that represent the original component state.
     *
     * @private
     */
    private props: any = {};

    /**
     * A list of extra, nested props added to make them available as models.
     *
     * @private
     */
    private nestedProps: any = {};

    /**
     * A list of props that have been "dirty" (changed) since the last request to the server.
     */
    private dirtyProps: {[key: string]: any} = {};

    /**
     * A list of dirty props that were sent to the server, but the response has
     * not yet been received.
     */
    private pendingProps: {[key: string]: any} = {};

    constructor(props: any, nestedProps: any) {
        this.props = props;
        this.nestedProps = nestedProps;
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

        if (this.dirtyProps[normalizedName] !== undefined) {
            return this.dirtyProps[normalizedName];
        }

        if (this.pendingProps[normalizedName] !== undefined) {
            return this.pendingProps[normalizedName];
        }

        if (this.nestedProps[normalizedName] !== undefined) {
            return this.nestedProps[normalizedName];
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
     *
     * Returns true if the new value is different from the existing value.
     */
    set(name: string, value: any): boolean {
        const normalizedName = normalizeModelName(name);

        const currentValue = this.get(normalizedName);

        if (currentValue === value) {
            return false;
        }

        this.dirtyProps[normalizedName] = value;

        return true;
    }

    getOriginalProps(): any {
        return { ...this.props };
    }

    getOriginalNestedProps(): any {
        return { ...this.nestedProps };
    }

    getDirtyProps(): any {
        return { ...this.dirtyProps };
    }

    /**
     * Called when an update request begins.
     */
    flushDirtyPropsToPending(): void {
        this.pendingProps = { ...this.dirtyProps };
        this.dirtyProps = {};
    }

    /**
     * Called when an update request finishes successfully.
     */
    reinitializeAllProps(props: any, nestedProps: any): void {
        this.props = props;
        this.nestedProps = nestedProps;
        this.pendingProps = {};
    }

    /**
     * Called after an update request failed.
     */
    pushPendingPropsBackToDirty(): void {
        // merge back onto dirty, but don't overwrite existing dirty props
        this.dirtyProps = { ...this.pendingProps, ...this.dirtyProps };
        this.pendingProps = {};
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
     * Returns true if any of the props changed.
     */
    reinitializeProvidedProps(props: any): boolean {
        let changed = false;

        for (const [key, value] of Object.entries(props)) {
            const currentValue = this.get(key);

            // if the readonly identifier is different, then overwrite the
            // prop entirely
            if (currentValue !== value) {
                changed = true;
                this.props[key] = value;
            }
        }

        return changed;
    }
}
