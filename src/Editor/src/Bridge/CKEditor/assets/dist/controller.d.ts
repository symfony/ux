import { AbstractWysiwygController, type WysiwygInstance } from '@symfony/ux-editor/format/wysiwyg';
export default class CKEditorController extends AbstractWysiwygController {
    static values: any;
    createEditor(mount: HTMLElement, config: Record<string, unknown>): Promise<WysiwygInstance>;
    serialize(instance: WysiwygInstance): string;
    hotReloadable(): Set<string>;
    applyConfig(diff: Record<string, unknown>, instance: any): Promise<void>;
    destroyEditor(instance: any): Promise<void>;
}
