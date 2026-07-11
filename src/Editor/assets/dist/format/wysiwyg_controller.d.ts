import { AbstractEditorController } from "../controller.js";
interface WysiwygInstance {
  getHTML(): string;
  destroy?(): void;
}
declare abstract class AbstractWysiwygController extends AbstractEditorController<WysiwygInstance> {
  static values: any;
  serialize(instance: WysiwygInstance): string;
  connect(): Promise<void>;
}
export { AbstractWysiwygController, WysiwygInstance };