import { AbstractEditorController } from '../controller.js';
export interface WysiwygInstance {
    getHTML(): string;
    destroy?(): void;
}
export declare abstract class AbstractWysiwygController extends AbstractEditorController<WysiwygInstance> {
    static values: any;
    serialize(instance: WysiwygInstance): string;
    connect(): Promise<void>;
}
