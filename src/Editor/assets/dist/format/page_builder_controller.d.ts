import { AbstractEditorController } from '../controller.js';
export interface PageInstance {
    getHtml(): string;
    getCss(): string;
    getComponents(): unknown[];
    getAssets(): unknown[];
    destroy?(): void;
}
export declare abstract class AbstractPageBuilderController extends AbstractEditorController<PageInstance> {
    serialize(instance: PageInstance): object;
}
