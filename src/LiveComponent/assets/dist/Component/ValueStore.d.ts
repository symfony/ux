export default class {
    private props;
    private dirtyProps;
    private pendingProps;
    private updatedPropsFromParent;
    constructor(props: any);
    get(name: string): any;
    has(name: string): boolean;
    set(name: string, value: any): boolean;
    getOriginalProps(): any;
    getDirtyProps(): any;
    getUpdatedPropsFromParent(): any;
    flushDirtyPropsToPending(): void;
    reinitializeAllProps(props: any): void;
    pushPendingPropsBackToDirty(): void;
    storeNewPropsFromParent(props: any): boolean;
}
