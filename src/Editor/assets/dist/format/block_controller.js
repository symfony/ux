import { t as AbstractEditorController } from "../controller-BoYyK5_A.js";
var AbstractBlockController = class extends AbstractEditorController {
	async serialize(instance) {
		return await instance.save();
	}
};
export { AbstractBlockController };
