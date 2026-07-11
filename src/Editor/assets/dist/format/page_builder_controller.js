import { t as AbstractEditorController } from "../controller-BoYyK5_A.js";
var AbstractPageBuilderController = class extends AbstractEditorController {
	serialize(instance) {
		return {
			html: instance.getHtml(),
			css: instance.getCss(),
			components: instance.getComponents(),
			assets: instance.getAssets()
		};
	}
};
export { AbstractPageBuilderController };
