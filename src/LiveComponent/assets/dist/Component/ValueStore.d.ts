export default class {
    private readonly identifierKey;
    updatedModels: string[];
    private props;
    constructor(props: any);
    get(name: string): any;
    has(name: string): boolean;
    set(name: string, value: any): boolean;
    all(): any;
    reinitializeAllProps(props: any): void;
    reinitializeProvidedProps(props: any): boolean;
    private isPropNameTopLevel;
    private findIdentifier;
}
