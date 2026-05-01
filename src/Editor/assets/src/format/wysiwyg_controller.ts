import { AbstractEditorController } from '../controller.js';

export interface WysiwygInstance {
  getHTML(): string;
  destroy?(): void;
}

export abstract class AbstractWysiwygController extends AbstractEditorController<WysiwygInstance> {
  static values = { ...(AbstractEditorController as any).values, sanitizeOnPaste: Boolean };

  serialize(instance: WysiwygInstance): string { return instance.getHTML(); }

  async connect(): Promise<void> {
    this.mountTarget.setAttribute('aria-multiline', 'true');
    await super.connect();
  }
}
