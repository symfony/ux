import { n as _defineProperty, t as AbstractEditorController } from "../controller-BoYyK5_A.js";
var AbstractWysiwygController = class extends AbstractEditorController {
	serialize(instance) {
		return instance.getHTML();
	}
	async connect() {
		this.mountTarget.setAttribute("aria-multiline", "true");
		await super.connect();
	}
};
_defineProperty(AbstractWysiwygController, "values", {
	...AbstractEditorController.values,
	sanitizeOnPaste: Boolean
});
export { AbstractWysiwygController };
