export default class {
    private props;
    private dirtyProps;
    private pendingProps;
    constructor(props: any);
    get(name: string): any;
    has(name: string): boolean;
    set(name: string, value: any): boolean;
    getOriginalProps(): any;
    getDirtyProps(): any;
    flushDirtyPropsToPending(): void;
    reinitializeAllProps(props: any): void;
    pushPendingPropsBackToDirty(): void;
    reinitializeProvidedProps(props: any): boolean;
}
