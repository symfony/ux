import { AbstractEditorController } from "../controller.js";
interface PageInstance {
  getHtml(): string;
  getCss(): string;
  getComponents(): unknown[];
  getAssets(): unknown[];
  destroy?(): void;
}
declare abstract class AbstractPageBuilderController extends AbstractEditorController<PageInstance> {
  serialize(instance: PageInstance): object;
}
export { AbstractPageBuilderController, PageInstance };