import { AbstractBlockController, type BlockInstance } from '@symfony/ux-editor/format/block';
export default class EditorJSController extends AbstractBlockController {
    static values: any;
    createEditor(mount: HTMLElement, config: Record<string, unknown>): Promise<BlockInstance>;
    hotReloadable(): Set<string>;
    applyConfig(diff: Record<string, unknown>, instance: any): Promise<void>;
    destroyEditor(instance: any): Promise<void>;
}
