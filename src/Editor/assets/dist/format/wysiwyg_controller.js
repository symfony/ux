import { AbstractEditorController } from '../controller.js';
export class AbstractWysiwygController extends AbstractEditorController {
    static values = { ...AbstractEditorController.values, sanitizeOnPaste: Boolean };
    serialize(instance) { return instance.getHTML(); }
    async connect() {
        this.mountTarget.setAttribute('aria-multiline', 'true');
        await super.connect();
    }
}
