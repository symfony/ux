import { AbstractPageBuilderController, type PageInstance } from '@symfony/ux-editor/format/page';
export default class GrapesJSController extends AbstractPageBuilderController {
    static values: any;
    createEditor(mount: HTMLElement, config: Record<string, unknown>): Promise<PageInstance>;
    serialize(instance: PageInstance): object;
    hotReloadable(): Set<string>;
    applyConfig(_diff: Record<string, unknown>, _instance: PageInstance): void;
    destroyEditor(instance: PageInstance): Promise<void>;
}
