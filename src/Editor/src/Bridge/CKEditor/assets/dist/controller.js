import { AbstractWysiwygController } from '@symfony/ux-editor/format/wysiwyg';
const READ_ONLY_LOCK_ID = 'symfony--ux-editor--ckeditor';
export default class CKEditorController extends AbstractWysiwygController {
    static values = { ...AbstractWysiwygController.values };
    async createEditor(mount, config) {
        const { default: ClassicEditor } = await import('@ckeditor/ckeditor5-build-classic');
        const editor = await ClassicEditor.create(mount, config);
        if (editor.model && typeof editor.model.document?.on === 'function') {
            editor.model.document.on('change:data', () => this.syncInput());
        }
        return editor;
    }
    serialize(instance) {
        return instance.getData();
    }
    hotReloadable() {
        return new Set(['readOnly', 'placeholder']);
    }
    async applyConfig(diff, instance) {
        const ck = instance;
        if ('readOnly' in diff) {
            if (diff.readOnly) {
                ck.enableReadOnlyMode(READ_ONLY_LOCK_ID);
            }
            else {
                ck.disableReadOnlyMode(READ_ONLY_LOCK_ID);
            }
        }
    }
    async destroyEditor(instance) {
        if (typeof instance?.destroy === 'function') {
            await instance.destroy();
        }
    }
}
