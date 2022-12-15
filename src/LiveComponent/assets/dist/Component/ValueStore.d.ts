export default class {
    updatedModels: string[];
    private props;
    private data;
    constructor(props: any, data: any);
    get(name: string): any;
    has(name: string): boolean;
    set(name: string, value: any): boolean;
    all(): any;
    reinitializeData(data: any): void;
    reinitializeProps(props: any): boolean;
}
