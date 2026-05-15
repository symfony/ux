import { AbstractWysiwygController, type WysiwygInstance } from '@symfony/ux-editor/format/wysiwyg';

interface CKEditorInstance extends WysiwygInstance {
    getData(): string;
    setData(data: string): void;
    enableReadOnlyMode(lockId: string): void;
    disableReadOnlyMode(lockId: string): void;
    isReadOnly: boolean;
}

const READ_ONLY_LOCK_ID = 'symfony--ux-editor--ckeditor';

export default class CKEditorController extends AbstractWysiwygController {
    static values = { ...(AbstractWysiwygController as any).values };

    async createEditor(mount: HTMLElement, config: Record<string, unknown>): Promise<WysiwygInstance> {
        const { default: ClassicEditor } = await import('@ckeditor/ckeditor5-build-classic');
        const editor = await ClassicEditor.create(mount, config);
        if (editor.model && typeof editor.model.document?.on === 'function') {
            editor.model.document.on('change:data', () => this.syncInput());
        }
        return editor as unknown as WysiwygInstance;
    }

    serialize(instance: WysiwygInstance): string {
        return (instance as CKEditorInstance).getData();
    }

    hotReloadable(): Set<string> {
        return new Set(['readOnly', 'placeholder']);
    }

    async applyConfig(diff: Record<string, unknown>, instance: any): Promise<void> {
        const ck = instance as CKEditorInstance;
        if ('readOnly' in diff) {
            if (diff.readOnly) {
                ck.enableReadOnlyMode(READ_ONLY_LOCK_ID);
            } else {
                ck.disableReadOnlyMode(READ_ONLY_LOCK_ID);
            }
        }
    }

    async destroyEditor(instance: any): Promise<void> {
        if (typeof instance?.destroy === 'function') {
            await instance.destroy();
        }
    }
}
