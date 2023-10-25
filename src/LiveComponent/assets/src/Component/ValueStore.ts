import { getDeepData } from '../data_manipulation_utils';
import { normalizeModelName } from '../string_utils';

export default class {
    /**
     * Original, read-only props that represent the original component state.
     */
    private props: any = {};

    /**
     * A list of props that have been "dirty" (changed) since the last request to the server.
     */
    private dirtyProps: {[key: string]: any} = {};

    /**
     * A list of dirty props that were sent to the server, but the response has
     * not yet been received.
     */
    private pendingProps: {[key: string]: any} = {};

    /**
     * A list of props that the parent wants us to update.
     *
     * These will be sent on the next request to the server.
     */
    private updatedPropsFromParent: {[key: string]: any} = {};

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

        if (this.dirtyProps[normalizedName] !== undefined) {
            return this.dirtyProps[normalizedName];
        }

        if (this.pendingProps[normalizedName] !== undefined) {
            return this.pendingProps[normalizedName];
        }

        if (this.props[normalizedName] !== undefined) {
            return this.props[normalizedName];
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

    getDirtyProps(): any {
        return { ...this.dirtyProps };
    }

    getUpdatedPropsFromParent(): any {
        return { ...this.updatedPropsFromParent };
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
    reinitializeAllProps(props: any): string[] {
        const changedProps = this.deepDiff(props);

        this.props = props;
        this.updatedPropsFromParent = {};
        this.pendingProps = {};

        return changedProps;
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
     * This is used when a parent component is rendering, and it includes
     * a fresh set of props that should be updated on the child component.
     *
     * The server manages returning only the props that should be updated onto
     * the child, so we don't need to worry about that.
     *
     * The props are stored in a different place, because the existing props
     * have their own checksum and these new props have *their* own checksum.
     * So, on the next render, both need to be sent independently.
     *
     * Returns true if any of the props are different.
     */
    storeNewPropsFromParent(props: any): boolean {
        let changed = false;

        for (const [key, value] of Object.entries(props)) {
            const currentValue = this.get(key);

            if (currentValue !== value) {
                changed = true;
            }
        }

        if (changed) {
            this.updatedPropsFromParent = props;
        }

        return changed;
    }

    private deepDiff(newObj: any, prefix = '', changedProps: string[] = []): string[] {
        for (const [key, value] of Object.entries(newObj)) {
            const currentPath = prefix ? `${prefix}.${key}` : key;
            const currentValue = this.get(currentPath);

            if (currentValue !== value) {
                if (typeof currentValue !== 'object' || typeof value !== 'object') {
                    // if a prop is dirty, the prop hasn't changed, because the dirty value will take precedence
                    if (this.dirtyProps[currentPath] !== undefined) {
                        continue;
                    }

                    changedProps.push(currentPath);
                } else {
                    this.deepDiff(value, currentPath, changedProps);
                }
            }
        }

        return changedProps;
    }
}
