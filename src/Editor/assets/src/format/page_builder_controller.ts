import { AbstractEditorController } from '../controller.js';

export interface PageInstance {
  getHtml(): string;
  getCss(): string;
  getComponents(): unknown[];
  getAssets(): unknown[];
  destroy?(): void;
}

export abstract class AbstractPageBuilderController extends AbstractEditorController<PageInstance> {
  serialize(instance: PageInstance): object {
    return {
      html: instance.getHtml(),
      css: instance.getCss(),
      components: instance.getComponents(),
      assets: instance.getAssets(),
    };
  }
}
