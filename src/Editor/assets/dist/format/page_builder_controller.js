import { AbstractEditorController } from '../controller.js';
export class AbstractPageBuilderController extends AbstractEditorController {
    serialize(instance) {
        return {
            html: instance.getHtml(),
            css: instance.getCss(),
            components: instance.getComponents(),
            assets: instance.getAssets(),
        };
    }
}
