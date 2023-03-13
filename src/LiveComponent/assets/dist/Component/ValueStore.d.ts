export default class {
    private props;
    private nestedProps;
    private dirtyProps;
    private pendingProps;
    constructor(props: any, nestedProps: any);
    get(name: string): any;
    has(name: string): boolean;
    set(name: string, value: any): boolean;
    getOriginalProps(): any;
    getOriginalNestedProps(): any;
    getDirtyProps(): any;
    flushDirtyPropsToPending(): void;
    reinitializeAllProps(props: any, nestedProps: any): void;
    pushPendingPropsBackToDirty(): void;
    reinitializeProvidedProps(props: any): boolean;
}
